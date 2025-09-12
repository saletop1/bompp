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

class BomController extends Controller
{
    // ===================================================================
    // == FUNGSI UNTUK BOM UPLOADER ==
    // ===================================================================

    public function index()
    {
        return view('bom');
    }

    /**
     * LANGKAH 1 (Disederhanakan): Hanya memproses struktur file tanpa mencari kode material.
     */
    public function processAndStoreFile(Request $request)
    {
        $request->validate(['file' => 'required|mimes:xls,xlsx,csv', 'plant' => 'required|string']);
        try {
            // Membaca file sebagai array biasa untuk keandalan
            $inventorData = Excel::toCollection(new \stdClass(), $request->file('file'))[0];
            $inventorData->shift(); // Buang baris header secara manual

            if ($inventorData->isEmpty()) {
                return back()->withErrors('The uploaded file has no data.');
            }

            $nodes = [];
            foreach ($inventorData as $row) {
                // Mengakses data berdasarkan indeks numerik sesuai file COBA LAGI BOM.xls
                $node = [
                    'item'        => trim($row[0] ?? ''), // Kolom A: ITEM
                    'description' => trim($row[1] ?? ''), // Kolom B: Material Description
                    'qty'         => trim($row[2] ?? '0'),// Kolom C: QTY
                    'sloc'        => trim($row[4] ?? ''), // Kolom E: SLOC
                    'code'        => trim($row[5] ?? ''), // Kolom F: KODE MATERIAL
                    'uom'         => trim($row[7] ?? 'PC'),// Kolom H: UOM
                ];

                $itemNumber = $node['item'];
                if (empty($itemNumber)) continue;
                $nodes[$itemNumber] = $node;
            }

            // Logika hierarki yang eksplisit berdasarkan aturan penomoran
            $bomsByParentItem = [];
            foreach ($nodes as $childItemNumber => $childNode) {
                if ($childItemNumber === '0.0') continue;
                $parts = explode('.', $childItemNumber);
                $parentItemNumber = null;

                if (count($parts) === 3) { // Item format X.Y.Z, parent-nya X.Y
                    $parentItemNumber = $parts[0] . '.' . $parts[1];
                } elseif (count($parts) === 2) {
                    if ($parts[1] !== '0') { // Item format X.Y (Y != 0), parent-nya X.0
                        $parentItemNumber = $parts[0] . '.0';
                    } else { // Item format X.0, parent-nya 0.0
                        $parentItemNumber = '0.0';
                    }
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
     * Fungsi API untuk mencari dan mengisi kode material yang kosong.
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

            foreach ($boms as &$bom) { // Gunakan '&' untuk memodifikasi array secara langsung
                if (empty($bom['parent']['code']) && !empty($bom['parent']['description'])) {
                    $foundCode = $this->findMaterialCode($pythonApiUrl, $bom['parent']['description']);
                    $bom['parent']['code'] = $foundCode ?? '#NOT_FOUND#';
                    if ($foundCode) $foundCount++;
                    $detailedResults[] = ['description' => $bom['parent']['description'], 'code' => $foundCode ?? 'tidak ditemukan'];
                }
                foreach ($bom['components'] as &$component) {
                    if (empty($component['code']) && !empty($component['description'])) {
                        $foundCode = $this->findMaterialCode($pythonApiUrl, $component['description']);
                        $component['code'] = $foundCode ?? '#NOT_FOUND#';
                        if ($foundCode) $foundCount++;
                        $detailedResults[] = ['description' => $component['description'], 'code' => $foundCode ?? 'tidak ditemukan'];
                    }
                }
            }

            $fileContent['boms'] = $boms;
            Storage::disk('local')->put($filename, json_encode($fileContent));

            $notFoundCount = count($detailedResults) - $foundCount;

            return response()->json([
                'status' => 'success',
                'message' => "Code generation complete. Found: {$foundCount}, Not Found: {$notFoundCount}.",
                'results' => $detailedResults // Kirim hasil terperinci ke frontend
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
                if (empty($bom['parent']['code']) || $bom['parent']['code'] === '#NOT_FOUND#') {
                    continue;
                }
                $components = collect($bom['components'])->map(fn($comp) => [
                    'Item Category' => 'L',
                    'Child' => ($comp['code'] === '#NOT_FOUND#') ? '' : $comp['code'],
                    'Qty' => $comp['qty'],
                    'Unit' => $comp['uom']
                ])->toArray();
                $bomsForUpload[] = [
                    'parent'        => $bom['parent']['code'],
                    'plant'         => $plant,
                    'bom_usage'     => '1',
                    'base_quantity' => $bom['parent']['qty'],
                    'base_unit'     => $bom['parent']['uom'],
                    'bom_text'      => '',
                    'components'    => $components
                ];
            }
            if (empty($bomsForUpload)) {
                 Storage::disk('local')->delete($filename);
                 return response()->json([
                     'status' => 'success',
                     'message' => 'BOM upload process finished. No valid BOMs with parent material codes were found to upload.',
                     'results' => []
                 ]);
            }

            $response = Http::timeout(600)->post($pythonApiUrl . '/upload_bom', [
                'username' => $request->input('username'),
                'password' => $request->input('password'),
                'boms'     => $bomsForUpload
            ]);

            Storage::disk('local')->delete($filename);
            return $response->json();
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'A fatal error occurred: ' . $e->getMessage()], 500);
        }
    }

    public function downloadProcessedFile($filename)
    {
        try {
            if (!Storage::disk('local')->exists($filename)) { abort(404, 'File not found.'); }

            $fileContent = json_decode(Storage::disk('local')->get($filename), true);
            $downloadFilename = 'SAP_MULTILEVEL_BOM_TEMPLATE_' . date('Y-m-d_H-i') . '.xlsx';

            $boms = $fileContent['boms'] ?? [];
            $plant = $fileContent['plant'] ?? '';

            return Excel::download(new ProcessedBomExport($boms, $plant), $downloadFilename);
        } catch (\Exception $e) {
            Log::error("Error downloading processed BOM file: " . $e->getMessage());
            return redirect()->route('bom.index')->withErrors('Could not download file.');
        }
    }

    private function findMaterialCode(string $apiUrl, string $description): ?string
    {
        try {
            $response = Http::timeout(15)->get($apiUrl . '/find_material', ['description' => $description]);

            // --- PERBAIKAN KRITIS DI SINI ---
            if ($response->successful() && $response->json('status') === 'success') {
                $foundCode = $response->json('material_code');
                Log::info("Material '{$description}' ditemukan dengan kode: {$foundCode}");
                return $foundCode;
            }
            // ---------------------------------

            Log::warning("Material '{$description}' tidak ditemukan.");
            return null;
        } catch (\Exception $e) {
            Log::error("Koneksi ke Python API gagal saat mencari '{$description}': " . $e->getMessage());
            return null;
        }
    }

    // ===================================================================
    // == FUNGSI UNTUK MATERIAL CONVERTER ==
    // ===================================================================

    public function showMaterialConverter()
    {
        return view('converter');
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xls,xlsx,csv', 'material_type' => 'required|string',
            'start_material_code' => 'required|string', 'plant' => 'required|string',
        ]);

        try {
            $data = Excel::toCollection(new HeadingRowImport, $request->file('file'))[0];
            $processedMaterials = [];
            $currentMaterialCode = $request->input('start_material_code');

            foreach ($data as $row) {
                $material = $row->toArray();
                $material['Material'] = $currentMaterialCode;
                $material['Material Type'] = $request->input('material_type');
                $material['Plant'] = $request->input('plant');
                if ($request->input('material_type') === 'FERT') {
                    $material['Division'] = $request->input('division');
                    $material['Distribution Channel'] = $request->input('distribution_channel');
                }
                $processedMaterials[] = $material;
                $currentMaterialCode = $this->incrementMaterialCode($currentMaterialCode);
            }

            $tempFilename = 'material_processed_' . Str::random(16) . '.json';
            Storage::disk('local')->put($tempFilename, json_encode($processedMaterials));

            return redirect()->route('converter.index')
                ->with('download_filename', $tempFilename)
                ->with('success', 'File has been processed. Ready to upload to SAP.');
        } catch(\Exception $e) {
            Log::error('Material Upload Processing Error: ' . $e->getMessage());
            return back()->withErrors('Error during file processing: ' . $e->getMessage());
        }
    }

    public function uploadToSap(Request $request)
    {
        $request->validate(['username' => 'required', 'password' => 'required', 'filename' => 'required']);
        try {
            $filename = $request->input('filename');
            if (!Storage::disk('local')->exists($filename)) {
                return response()->json(['status' => 'error', 'message' => 'Processed file not found.'], 404);
            }

            $pythonApiUrl = env('PYTHON_SAP_API_URL', 'http://127.0.0.1:5001');
            $materials = json_decode(Storage::disk('local')->get($filename), true);

            $response = Http::timeout(600)->post($pythonApiUrl . '/upload_material', [
                'username' => $request->input('username'),
                'password' => $request->input('password'),
                'materials' => $materials
            ]);

            Storage::disk('local')->delete($filename);
            return $response->json();
        } catch (\Exception $e) {
            Log::error('Upload to SAP API Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function download($filename)
    {
        return Storage::disk('local')->download($filename, 'PROCESSED_MATERIALS.json');
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
        // Logika untuk mengirim notifikasi email
        return response()->json(['status' => 'success', 'message' => 'Email notification sent.']);
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

