<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Exports\ProcessedBomExport;
use App\Imports\HeadingRowImport;
use App\Exports\MaterialMasterExport;
use Illuminate\Support\Facades\Mail;
use App\Mail\SapUploadNotification;

class BomController extends Controller
{
    // ===================================================================
    // == FUNGSI UNTUK BOM UPLOADER ==
    // ===================================================================

    public function index()
    {
        return view('bom');
    }

    public function processAndStoreFile(Request $request)
    {
        $request->validate(['file' => 'required|mimes:xls,xlsx,csv', 'plant' => 'required|string']);
        try {
            $collection = Excel::toCollection(null, $request->file('file'))->first();
             if ($collection->isEmpty() || $collection->count() <= 1) {
                return back()->withErrors(['file' => 'File yang Anda upload kosong atau hanya berisi header.']);
            }

            // Mengambil header dan mengubahnya menjadi huruf kecil untuk perbandingan case-insensitive
            $header = array_map('strtolower', $collection->first()->toArray());
            $inventorData = $collection->slice(1);

            // Helper function to find a value from a row using multiple possible header names
            $findValue = function(array $rowData, array $keys, $default = '') {
                foreach ($keys as $key) {
                    if (isset($rowData[$key]) && !is_null($rowData[$key])) {
                        return trim((string) $rowData[$key]);
                    }
                }
                return $default;
            };

            $nodes = [];
            foreach ($inventorData as $row) {
                // Skip empty rows
                if (empty(array_filter($row->toArray()))) {
                    continue;
                }

                $rowData = array_combine($header, $row->toArray());

                // ## PERUBAHAN DI SINI (HANYA UNTUK BOM UPLOADER) ##
                // Menyesuaikan header untuk parent ("Material Description", "UOM") dan
                // komponen ("Description2", "uom1")
                $node = [
                    'item'        => $findValue($rowData, ['item', 'item number']),
                    'description' => $findValue($rowData, ['material description', 'description2', 'description', 'part name']),
                    'qty'         => $findValue($rowData, ['qty', 'quantity'], '0'),
                    'sloc'        => $findValue($rowData, ['sloc', 'storage location']),
                    'code'        => $findValue($rowData, ['part number', 'code', 'material']),
                    'uom'         => $findValue($rowData, ['uom', 'uom1', 'unit'], 'PC'),
                ];

                $itemNumber = $node['item'];
                if (empty($itemNumber)) continue;
                $nodes[$itemNumber] = $node;
            }

            $bomsByParentItem = [];
            foreach ($nodes as $childItemNumber => $childNode) {
                if ($childItemNumber === '0.0') continue;
                $parts = explode('.', $childItemNumber);
                $parentItemNumber = null;
                if (count($parts) === 3) { $parentItemNumber = $parts[0] . '.' . $parts[1]; }
                elseif (count($parts) === 2) {
                    if ($parts[1] !== '0') { $parentItemNumber = $parts[0] . '.0'; }
                    else { $parentItemNumber = '0.0'; }
                }
                if ($parentItemNumber !== null && isset($nodes[$parentItemNumber])) {
                    $parent = $nodes[$parentItemNumber];
                    if (!isset($bomsByParentItem[$parentItemNumber])) {
                        $bomsByParentItem[$parentItemNumber] = [ 'parent' => $parent, 'components' => [] ];
                    }
                    $bomsByParentItem[$parentItemNumber]['components'][] = $childNode;
                }
            }

            $tempFilename = 'bom_processed_' . Str::random(16) . '.json';
            $bomPayload = [ 'plant' => $request->input('plant'), 'boms'  => array_values($bomsByParentItem) ];
            Log::info('Initial BOM Structure Saved:', $bomPayload);
            Storage::disk('local')->put($tempFilename, json_encode($bomPayload));

            return redirect()->route('bom.index')
                ->with('processed_filename', $tempFilename)
                ->with('success', 'File structure has been processed. Ready to generate material codes.');
        } catch (\Exception $e) {
            Log::error('BOM Processing Error: ' . $e->getMessage());
            return back()->withErrors('Error during file processing: ' . $e->getMessage());
        }
    }

    /**
     * [DIPERBAIKI] Fungsi API untuk mencari kode material dengan logika yang lebih aman.
     */
    public function generateBomMaterialCodes(Request $request)
    {
        $request->validate(['filename' => 'required|string']);
        try {
            $filename = $request->input('filename');
            if (!Storage::disk('local')->exists($filename)) {
                return response()->json(['status' => 'error', 'message' => 'Processed file not found.'], 404);
            }

            $pythonApiUrl = env('PYTHON_SAP_API_URL', 'http://127.0.0.1:5001');
            $fileContent = json_decode(Storage::disk('local')->get($filename), true);
            $boms = $fileContent['boms'];

            $detailedResults = [];
            $foundCount = 0;
            $updatedBoms = []; // Array baru yang bersih untuk menyimpan hasil

            foreach ($boms as $bom) {
                $parentData = $bom['parent'];
                if (empty($parentData['code']) && !empty($parentData['description'])) {
                    $foundCode = $this->findMaterialCode($pythonApiUrl, $parentData['description']);
                    $parentData['code'] = $foundCode ?? '#NOT_FOUND#';
                    if ($foundCode) $foundCount++;
                    $detailedResults[] = ['description' => $parentData['description'], 'code' => $foundCode ?? 'tidak ditemukan'];
                }

                $updatedComponents = [];
                foreach ($bom['components'] as $component) {
                    // Salin data asli ke array baru untuk memastikan tidak ada referensi silang
                    $newComponentData = $component;

                    if (empty($newComponentData['code']) && !empty($newComponentData['description'])) {
                        $foundCode = $this->findMaterialCode($pythonApiUrl, $newComponentData['description']);
                        $newComponentData['code'] = $foundCode ?? '#NOT_FOUND#'; // Hanya ubah 'code'
                        if ($foundCode) $foundCount++;
                        $detailedResults[] = ['description' => $newComponentData['description'], 'code' => $foundCode ?? 'tidak ditemukan'];
                    }
                    $updatedComponents[] = $newComponentData;
                }

                $updatedBoms[] = [
                    'parent' => $parentData,
                    'components' => $updatedComponents
                ];
            }

            $fileContent['boms'] = $updatedBoms; // Ganti data lama dengan data baru yang bersih
            Storage::disk('local')->put($filename, json_encode($fileContent));

            $notFoundCount = count($detailedResults) - $foundCount;

            return response()->json([
                'status' => 'success',
                'message' => "Code generation complete. Found: {$foundCount}, Not Found: {$notFoundCount}.",
                'results' => $detailedResults
            ]);

        } catch (\Exception $e) {
            Log::error('BOM Code Generation Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'An error occurred during code generation.'], 500);
        }
    }

    public function uploadProcessedBom(Request $request)
    {
        $request->validate(['username' => 'required|string', 'password' => 'required|string', 'filename' => 'required|string']);
        try {
            $filename = $request->input('filename');
            if (!Storage::disk('local')->exists($filename)) {
                return response()->json(['status' => 'error', 'message' => 'Processed file not found.'], 404);
            }
            $pythonApiUrl = env('PYTHON_SAP_API_URL', 'http://127.0.0.1:5001');
            $fileContent = json_decode(Storage::disk('local')->get($filename), true);
            $plant = $fileContent['plant'];
            $boms = $fileContent['boms'];
            $bomsForUpload = [];
            foreach ($boms as $bom) {
                if (empty($bom['parent']['code']) || $bom['parent']['code'] === '#NOT_FOUND#') continue;

                $components = collect($bom['components'])->map(fn($comp) => [
                    'Item Category' => 'L',
                    'Child' => ($comp['code'] === '#NOT_FOUND#') ? '' : $comp['code'],
                    'Qty' => $comp['qty'],
                    'Unit' => $comp['uom'],
                    'sloc' => $comp['sloc'] ?? ''
                ])->toArray();

                $bomsForUpload[] = [
                    'parent' => $bom['parent']['code'],
                    'plant' => $plant,
                    'bom_usage' => '1',
                    'base_quantity' => $bom['parent']['qty'],
                    'base_unit' => $bom['parent']['uom'],
                    'bom_text' => '',
                    'components' => $components
                ];
            }
            if (empty($bomsForUpload)) {
                 Storage::disk('local')->delete($filename);
                 return response()->json(['status' => 'success', 'message' => 'BOM upload process finished. No valid BOMs with parent material codes were found to upload.', 'results' => []]);
            }
            $response = Http::timeout(600)->post($pythonApiUrl . '/upload_bom', ['username' => $request->input('username'), 'password' => $request->input('password'), 'boms' => $bomsForUpload]);
            Storage::disk('local')->delete($filename);
            return $response->json();
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'A fatal error occurred: ' . $e->getMessage()], 500);
        }
    }

    public function downloadProcessedFile($filename)
    {
        try {
            if (!Storage::disk('local')->exists($filename) || !Str::startsWith($filename, 'bom_processed_')) abort(404, 'File not found or invalid.');
            $fileContent = json_decode(Storage::disk('local')->get($filename), true);
            $downloadFilename = 'SAP_MULTILEVEL_BOM_TEMPLATE_' . date('Y-m-d_H-i') . '.xlsx';
            return Excel::download(new ProcessedBomExport($fileContent['boms'] ?? [], $fileContent['plant'] ?? ''), $downloadFilename);
        } catch (\Exception $e) {
            Log::error("Error downloading processed BOM file: " . $e->getMessage());
            return redirect()->route('bom.index')->withErrors('Could not download file.');
        }
    }

    private function findMaterialCode(string $apiUrl, string $description): ?string
    {
        try {
            $response = Http::timeout(15)->get($apiUrl . '/find_material', ['description' => $description]);
            if ($response->successful() && $response->json('status') === 'success') {
                $foundCode = $response->json('material_code');
                Log::info("Material '{$description}' ditemukan dengan kode: {$foundCode}");
                return $foundCode;
            }
            Log::warning("Material '{$description}' tidak ditemukan.");
            return null;
        } catch (\Exception $e) {
            Log::error("Koneksi ke Python API gagal saat mencari '{$description}': " . $e->getMessage());
            return null;
        }
    }

    // ===================================================================
    // == FUNGSI UNTUK MATERIAL CONVERTER (DARI ExcelConverterController) ==
    // ===================================================================

    public function showMaterialConverter()
    {
        return view('converter');
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xls,xlsx,csv', 'start_material_code' => 'required|string',
            'material_type' => 'required|string', 'plant' => 'required|string',
            'division' => ['required_if:material_type,FERT', 'nullable', 'string'],
            'distribution_channel' => ['required_if:material_type,FERT', 'nullable', 'string'],
        ]);

        try {
            $collection = Excel::toCollection(null, $request->file('file'))->first();
            if ($collection->isEmpty() || $collection->count() <= 1) {
                return back()->withErrors(['file' => 'File yang Anda upload kosong atau hanya berisi header.']);
            }

            $inventorHeader = array_map('strtolower', $collection->first()->toArray());
            $inventorData = $collection->slice(1);

            $divisionMap = [
                '01' => ['acct_group' => '01', 'val_class' => 'FG01', 'mat_group' => 'FFG001'], '02' => ['acct_group' => '02', 'val_class' => 'FG02', 'mat_group' => 'FFG009'],
                '03' => ['acct_group' => '03', 'val_class' => 'FG06', 'mat_group' => 'FFG008'], '04' => ['acct_group' => '04', 'val_class' => 'FG04', 'mat_group' => 'FFG004'],
                '05' => ['acct_group' => '05', 'val_class' => 'FG03', 'mat_group' => 'FFG002'], '06' => ['acct_group' => '06', 'val_class' => 'FG05', 'mat_group' => 'FFG003'],
                '07' => ['acct_group' => '07', 'val_class' => 'FG07', 'mat_group' => 'FFG007'], '08' => ['acct_group' => '08', 'val_class' => 'FG10', 'mat_group' => 'FFG005'],
                '09' => ['acct_group' => '09', 'val_class' => 'FG01', 'mat_group' => 'FFG001'], '10' => ['acct_group' => '10', 'val_class' => 'FG08', 'mat_group' => 'FFG007'],
                '00' => ['acct_group' => '00', 'val_class' => 'SF01', 'mat_group' => ''],
            ];

            // ## DIKEMBALIKAN KE SEMULA (TIDAK ADA PERUBAHAN) ##
            $columnMapping = [
                "Material Description" => "Material Description", "Base Unit of Measure" => "Base Unit of Measure",
                "Dimension" => "Dimension", "MRP GROUP" => "MRP GROUP", "MRP Controller" => "MRP Controller",
                "Material Group" => "Material Group", "Storage Location" => "Storage Location",
                "Production Storage Location" => "Storage Location", "Document" => "Document"
            ];

            $sapHeader = [ 'Material', 'Industry Sector', 'Old material number', 'Material Type', 'Material Group', 'Base Unit of Measure', 'Material Description', 'Division', 'General item cat group', 'Prod./insp. Memo', 'Document', 'Ind. Std Desc', 'Dimension', 'Plant', 'Storage Location', 'Sales Organization', 'Distribution Channel', 'Delivery Plant', 'Sales Unit', 'Tax Country', 'Tax Class', 'Tax Cat', 'Item Category Group', 'Acct assignment grp', 'Mat Group 1', 'Mat Group 2', 'Mat Group 3', 'Mat Group 4', 'Mat Group 5', 'Trans Group', 'Loadin Group', 'Material Package', 'Mat pack type', 'Batch Management', 'Profit Center', 'Valuation Class', 'StandardPrc', 'MovingAvg', 'Price Unit', 'Price Control', 'Price Unit Hard Currency', 'Denominator', 'Alternative UoM', 'Numerator', 'Length', 'Width', 'Height', 'Unit of Dimension', 'Gross Weight', 'Weight Unit', 'Net Weight', 'Volume', 'Purchasing Group', 'Volume Unit', 'Proportion unit', 'Class', 'WARNA ', 'VOLUME PRODUCT', 'MRP Type', 'MRP GROUP', 'MRP Controller', 'Lot Size', 'Min Lot Size', 'Max Lot Size', 'Rounding Value', 'Procurement Type', 'Special Procurement Type', 'Backflush Indicator', 'Inhouse Production', 'Pl. Deliv. Time', 'GR Processing Time', 'Schedulled Margin Key', 'Safety Stock', 'Strategy Group', 'Consumption Mode', 'Forward Consumption Period', 'Backward Consumption Period', 'period indicator', 'fiscal year', 'Availability Check', 'Selection Method', 'Individual Collective', 'Unit Of Issue', 'Production Storage Location', 'Storage loc. for EP', 'Prod Schedule Profile', 'Under Delivery Tolerance', 'Over Delivery Tolerance', 'Unlt Deliv Tol', 'Material-related origin', 'Ind Qty Structure', 'Costing lot size', 'Do Not Cost', 'Plant-sp.matl status', 'Stock Determination Group', 'Unnamed: 95', 'Inspection Type', 'Inspection With Task List' ];

            $sapData = [];
            $currentMaterialCode = $request->input('start_material_code');
            $selectedMaterialType = $request->input('material_type');
            $selectedPlant = $request->input('plant');

            foreach ($inventorData as $inventorRow) {
                if ($inventorRow->filter()->isEmpty()) continue;
                $rowData = array_combine($inventorHeader, $inventorRow->toArray());

                $tempSapRow = array_fill_keys($sapHeader, '');

                foreach ($columnMapping as $sapCol => $inventorCol) {
                    $inventorColLower = strtolower($inventorCol);
                    if (isset($rowData[$inventorColLower])) {
                        $tempSapRow[$sapCol] = strtoupper(trim((string)$rowData[$inventorColLower]));
                    }
                }

                $profitCenterMap = [ '3000' => '300301', '2000' => '200301', '1000' => '100301' ];
                $tempSapRow["Material"] = $currentMaterialCode;
                $currentMaterialCode = $this->incrementMaterialCode($currentMaterialCode);
                $tempSapRow["Material Type"] = $selectedMaterialType; $tempSapRow["Plant"] = $selectedPlant;
                $tempSapRow["Profit Center"] = $profitCenterMap[$selectedPlant] ?? '';
                $tempSapRow["Price Control"] = "S"; $tempSapRow["Industry Sector"] = "F"; $tempSapRow["General item cat group"] = "NORM";
                $tempSapRow["Batch Management"] = "X"; $tempSapRow["Valuation Class"] = "SF01"; $tempSapRow["Price Unit"] = "1";
                $tempSapRow["Class"] = "PRODUCTION"; $tempSapRow["MRP Type"] = "PD"; $tempSapRow["Lot Size"] = "EX";
                $tempSapRow["Procurement Type"] = "E"; $tempSapRow["Backflush Indicator"] = "1"; $tempSapRow["Schedulled Margin Key"] = "000";
                $tempSapRow["Strategy Group"] = "40"; $tempSapRow["period indicator"] = "M"; $tempSapRow["Availability Check"] = "KP";
                $tempSapRow["Individual Collective"] = "1"; $tempSapRow["Prod Schedule Profile"] = "000002"; $tempSapRow["Material-related origin"] = "X";
                $tempSapRow["Ind Qty Structure"] = "X"; $tempSapRow["Plant-sp.matl status"] = "03"; $tempSapRow["Stock Determination Group"] = "0001";
                $tempSapRow["Inspection Type"] = "04"; $tempSapRow["Inspection With Task List"] = "X"; $tempSapRow["Costing lot size"] = "100";

                if ($selectedMaterialType === 'FERT') {
                    $tempSapRow['Sales Organization'] = '1000'; $tempSapRow['Tax Country'] = 'ID'; $tempSapRow['Tax Class'] = '1';
                    $tempSapRow['Tax Cat'] = 'ZPPN'; $tempSapRow['Item Category Group'] = 'Z001'; $tempSapRow['Trans Group'] = '0001';
                    $tempSapRow['Loading Group'] = '0001'; $tempSapRow['Material Package'] = 'ZMG1';
                    $selectedDivision = $request->input('division');
                    $tempSapRow['Division'] = $selectedDivision;
                    $tempSapRow['Distribution Channel'] = $request->input('distribution_channel');
                    if (isset($divisionMap[$selectedDivision])) {
                        $tempSapRow['Acct assignment grp'] = $divisionMap[$selectedDivision]['acct_group'];
                        $tempSapRow['Valuation Class'] = $divisionMap[$selectedDivision]['val_class'];
                        $tempSapRow['Material Group'] = $divisionMap[$selectedDivision]['mat_group'];
                    }
                } elseif ($selectedMaterialType === 'VERP') {
                    $tempSapRow['Division'] = 'M5'; $tempSapRow['Valuation Class'] = 'PK01';
                }

                $sapData[] = $tempSapRow;
            }

            if (empty($sapData)) {
                return back()->withErrors(['file' => 'Tidak ada baris data yang valid untuk diproses.']);
            }

            $tempFilename = 'material_processed_' . Str::random(16) . '.json';
            Storage::disk('local')->put($tempFilename, json_encode($sapData));

            return redirect()->route('converter.index')
                ->with('download_filename', $tempFilename)
                ->with('success', count($sapData) . ' baris data berhasil diproses')
                ->with('processed_plant', $request->input('plant'));
        } catch(\Exception $e) {
            Log::error('Material Upload Processing Error: ' . $e->getMessage());
            return back()->withErrors('Error during file processing: ' . $e->getMessage());
        }
    }

    public function uploadToSap(Request $request)
    {
        $request->validate(['username' => 'required', 'password' => 'required', 'filename' => 'required']);
        try {
            $filename = 'material_processed_' . Str::after($request->input('filename'), 'material_processed_');
            if (!Storage::disk('local')->exists($filename)) {
                return response()->json(['status' => 'error', 'message' => 'Processed file not found.'], 404);
            }
            $pythonApiUrl = env('PYTHON_SAP_API_URL', 'http://127.0.0.1:5001');
            $materials = json_decode(Storage::disk('local')->get($filename), true);
            $response = Http::timeout(600)->post($pythonApiUrl . '/upload_material', ['username' => $request->input('username'),'password' => $request->input('password'),'materials' => $materials]);
            Storage::disk('local')->delete($filename);
            return $response->json();
        } catch (\Exception $e) {
            Log::error('Upload to SAP API Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function download($filename)
    {
        $filePath = 'material_processed_' . Str::after($filename, 'material_processed_');
        try {
            if (!Storage::disk('local')->exists($filePath)) abort(404, 'File not found.');
            $processedData = json_decode(Storage::disk('local')->get($filePath), true);
            $downloadFilename = 'SAP_MATERIAL_MASTER_TEMPLATE_' . date('Y-m-d_H-i') . '.xlsx';
            return Excel::download(new MaterialMasterExport($processedData), $downloadFilename);
        } catch (\Exception $e) {
            Log::error("Error downloading processed material file: " . $e->getMessage());
            return redirect()->route('converter.index')->withErrors('Could not download the file due to an error.');
        }
    }

    public function activateQm(Request $request)
    {
        $request->validate(['username' => 'required', 'password' => 'required', 'materials' => 'required|array']);
        try {
            $pythonApiUrl = env('PYTHON_SAP_API_URL', 'http://127.0.0.1:5001');
            $response = Http::post($pythonApiUrl . '/activate_qm', $request->all());
            return $response->json();
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function generateNextMaterialCode(Request $request)
    {
        $request->validate(['material_type' => 'required|string']);
        try {
            $pythonApiUrl = env('PYTHON_SAP_API_URL', 'http://127.0.0.1:5001');
            $response = Http::get($pythonApiUrl . '/get_next_material', $request->all());
            return $response->json();
        } catch (\Exception $e) {
            Log::error('Could not connect to Python API for generating material code: ' . $e->getMessage());
            return response()->json(['error' => 'Could not connect to the processing service.'], 500);
        }
    }

    public function sendNotification(Request $request)
    {
        $request->validate(['recipient' => 'required|email', 'results' => 'required|array']);
        try {
            Mail::to($request->input('recipient'))->send(new SapUploadNotification($request->input('results')));
            return response()->json(['message' => 'Email sent successfully!']);
        } catch (\Exception $e) {
            Log::error('Email sending failed: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to send email.'], 500);
        }
    }

    private function incrementMaterialCode(string $code): string
    {
        if (preg_match('/(.*\D)?(\d+)$/', $code, $matches)) {
            $prefix = $matches[1] ?? '';
            $numberStr = $matches[2];
            $number = intval($numberStr);
            $padding = strlen($numberStr);
            return $prefix . str_pad($number + 1, $padding, '0', STR_PAD_LEFT);
        }
        return $code . '-1';
    }
}

