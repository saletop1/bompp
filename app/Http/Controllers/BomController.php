<?php

namespace App\Http\Controllers;

// Ditambahkan untuk custom import
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

// Use statement yang sudah ada
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Exports\ProcessedBomExport;
use App\Exports\MaterialMasterExport;
use App\Exports\SapUploadReportExport;
use Illuminate\Support\Facades\Mail;
use App\Mail\SapUploadNotification;
use App\Exports\RoutingTemplateExport;
use App\Mail\MaterialUploadNotification;

/**
 * Custom import class untuk BOM Uploader.
 * Class ini memastikan semua nilai sel dibaca sebagai string
 * untuk menjaga angka nol di depan (leading zeros) pada kode material.
 */
class BomImport extends DefaultValueBinder implements ToCollection, WithCustomValueBinder
{
    public $data;

    public function collection(\Illuminate\Support\Collection $rows)
    {
        $this->data = $rows;
    }

    public function bindValue(Cell $cell, $value)
    {
        // Memaksa setiap sel untuk diperlakukan sebagai string
        $cell->setValueExplicit($value, DataType::TYPE_STRING);
        return true;
    }
}

/**
 * @mixin \Maatwebsite\Excel\Excel
 */
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
            $import = new BomImport();
            Excel::import($import, $request->file('file'));
            $collection = $import->data;

            if ($collection->isEmpty() || $collection->count() <= 1) {
                return back()->withErrors(['file' => 'File yang Anda upload kosong atau hanya berisi header.']);
            }

            $header = array_map('strtolower', $collection->first()->toArray());

            $requiredHeaders = ['item', 'material description', 'qty', 'uom'];
            $missingHeaders = array_diff($requiredHeaders, $header);

            if (!empty($missingHeaders)) {
                $errorMessage = 'File rejected. The file header is invalid. The following required columns are missing: ' . implode(', ', $missingHeaders);
                return back()->withErrors(['file' => $errorMessage]);
            }

            $inventorData = $collection->slice(1);

            $findValue = function(array $rowData, array $keys, $default = '') {
                foreach ($keys as $key) {
                    if (isset($rowData[$key]) && !is_null($rowData[$key]) && $rowData[$key] !== '') {
                        return preg_replace('/\s+/', ' ', trim((string) $rowData[$key]));
                    }
                }
                return $default;
            };

            $nodes = [];
            foreach ($inventorData as $row) {
                if (empty(array_filter($row->toArray()))) {
                    continue;
                }

                $rowData = array_combine($header, $row->toArray());
                $itemNumber = $findValue($rowData, ['item']);
                if (empty($itemNumber)) continue;

                $partNode = [
                    'item'        => $itemNumber,
                    'description' => $findValue($rowData, ['material description']),
                    'qty'         => str_replace(',', '.', $findValue($rowData, ['qty'], '1')),
                    'sloc'        => $findValue($rowData, ['sloc']),
                    'code'        => '',
                    'uom'         => $findValue($rowData, ['uom'], 'PC'),
                ];
                $nodes[$itemNumber] = $partNode;

                $rawMaterialCode = $findValue($rowData, ['kode material']);
                if (!empty($rawMaterialCode)) {
                    $materialNode = [
                        'item'        => $itemNumber . '.1',
                        'description' => $findValue($rowData, ['description2']),
                        'qty'         => str_replace(',', '.', $findValue($rowData, ['unit of issue'], '0')),
                        'sloc'        => $findValue($rowData, ['sloc']),
                        'code'        => $rawMaterialCode,
                        'uom'         => $findValue($rowData, ['uom1'], 'PC'),
                    ];
                    $nodes[$itemNumber . '.1'] = $materialNode;
                }
            }

            $bomsByParentItem = [];
            foreach ($nodes as $childItemNumber => $childNode) {
                if ($childItemNumber === '0.0') {
                    continue;
                }

                $parts = explode('.', $childItemNumber);
                $parentItemNumber = null;

                $level = count($parts);

                if ($level === 2) {
                    if ($parts[1] !== '0') {
                        $parentItemNumber = $parts[0] . '.0';
                    } else {
                        $parentItemNumber = '0.0';
                    }
                } elseif ($level > 2) {
                    array_pop($parts);
                    $parentItemNumber = implode('.', $parts);
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
                ->with('success', 'File structure has been processed. Ready to generate material codes.')
                ->with('processed_plant', $request->input('plant'));
        } catch (\Exception $e) {
            Log::error('BOM Processing Error: ' . $e->getMessage());
            return back()->withErrors('Error during file processing: ' . $e->getMessage());
        }
    }

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
            $updatedBoms = [];

            foreach ($boms as $bom) {
                $parentData = $bom['parent'];
                if (empty($parentData['code']) && !empty($parentData['description'])) {
                    $foundCode = $this->findMaterialCode($pythonApiUrl, $parentData['description']);

                    if ($foundCode) {
                        $parentData['code'] = $foundCode;
                        $foundCount++;
                        $detailedResults[] = ['description' => $parentData['description'], 'code' => $foundCode];
                    } else {
                        $parentData['code'] = '#NOT_FOUND#';
                        $detailedResults[] = ['description' => $parentData['description'], 'code' => 'tidak ditemukan'];
                    }
                }

                $updatedComponents = [];
                foreach ($bom['components'] as $component) {
                    $newComponentData = $component;

                    if (empty($newComponentData['code']) && !empty($newComponentData['description'])) {
                        $foundCode = $this->findMaterialCode($pythonApiUrl, $newComponentData['description']);

                        if ($foundCode) {
                            $newComponentData['code'] = $foundCode;
                            $foundCount++;
                            $detailedResults[] = ['description' => $newComponentData['description'], 'code' => $foundCode];
                        } else {
                            $newComponentData['code'] = '#NOT_FOUND#';
                            $detailedResults[] = ['description' => $newComponentData['description'], 'code' => 'tidak ditemukan'];
                        }
                    }
                    $updatedComponents[] = $newComponentData;
                }

                $updatedBoms[] = [
                    'parent' => $parentData,
                    'components' => $updatedComponents
                ];

            }

            // ===================================================================
            // == LOGIKA BARU: Membersihkan dan menggabungkan data duplikat ==
            // ===================================================================
            Log::info('Starting BOM deduplication process.');
            $cleanedBomsMap = [];

            foreach ($updatedBoms as $bom) {
                $parent = $bom['parent'];
                // Gunakan kode sebagai kunci utama, fallback ke deskripsi jika kode tidak ada/tidak ditemukan
                $parentKey = (!empty($parent['code']) && $parent['code'] !== '#NOT_FOUND#')
                             ? $parent['code']
                             : strtolower(trim($parent['description']));

                if (!isset($cleanedBomsMap[$parentKey])) {
                    // Jika parent ini belum ada, tambahkan sebagai entri baru.
                    $cleanedBomsMap[$parentKey] = $bom;
                    // Pastikan komponen di dalamnya juga unik.
                    $componentMap = [];
                    foreach ($bom['components'] as $component) {
                        $componentKey = (!empty($component['code']) && $component['code'] !== '#NOT_FOUND#')
                                        ? $component['code']
                                        : strtolower(trim($component['description']));
                        if (!isset($componentMap[$componentKey])) {
                            $componentMap[$componentKey] = $component;
                        }
                    }
                    $cleanedBomsMap[$parentKey]['components'] = array_values($componentMap);
                } else {
                    // Jika parent sudah ada, gabungkan (merge) komponennya.
                    $existingComponents = $cleanedBomsMap[$parentKey]['components'];
                    $componentMap = [];

                    // Petakan komponen yang sudah ada untuk pengecekan duplikat
                    foreach ($existingComponents as $component) {
                        $componentKey = (!empty($component['code']) && $component['code'] !== '#NOT_FOUND#')
                                        ? $component['code']
                                        : strtolower(trim($component['description']));
                        $componentMap[$componentKey] = $component;
                    }

                    // Tambahkan komponen baru dari BOM saat ini, hanya jika belum ada.
                    foreach ($bom['components'] as $newComponent) {
                        $componentKey = (!empty($newComponent['code']) && $newComponent['code'] !== '#NOT_FOUND#')
                                        ? $newComponent['code']
                                        : strtolower(trim($newComponent['description']));
                        if (!isset($componentMap[$componentKey])) {
                            $componentMap[$componentKey] = $newComponent;
                        }
                    }
                    // Perbarui daftar komponen untuk parent ini.
                    $cleanedBomsMap[$parentKey]['components'] = array_values($componentMap);
                }
            }

            // Konversi map kembali ke array numerik
            $finalCleanedBoms = array_values($cleanedBomsMap);
            Log::info('BOM deduplication finished. Count before: ' . count($updatedBoms) . ', Count after: ' . count($finalCleanedBoms));

            // Timpa file JSON dengan data yang sudah bersih
            $fileContent['boms'] = $finalCleanedBoms;
            Storage::disk('local')->put($filename, json_encode($fileContent));

            $notFoundCount = count($detailedResults) - $foundCount;

            return response()->json([
                'status' => 'success',
                'message' => "Code generation and data cleaning complete. Found: {$foundCount}, Not Found: {$notFoundCount}.",
                'results' => $detailedResults
            ]);

        } catch (\Exception $e) {
            Log::error('BOM Code Generation Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'An error occurred during code generation.'], 500);
        }
    }

    public function downloadRoutingTemplate(Request $request, $filename)
    {
        try {
            if (!Storage::disk('local')->exists($filename) || !Str::startsWith($filename, 'bom_processed_')) {
                abort(404, 'File not found or invalid.');
            }

            $fileContent = json_decode(Storage::disk('local')->get($filename), true);
            $boms = $fileContent['boms'] ?? [];
            $plant = $fileContent['plant'] ?? '';
            $dataForExport = [];

            foreach ($boms as $bom) {
                if (empty($bom['parent']) || empty($bom['parent']['code']) || $bom['parent']['code'] === '#NOT_FOUND#') {
                    continue;
                }
                $parent = $bom['parent'];

                // Membuat satu baris data untuk setiap parent BOM
                $dataForExport[] = [
                    'Material'      => ltrim($parent['code'], '0'), // Menghapus nol di depan untuk tampilan
                    'Plant'         => $plant,
                    'Description'   => $parent['description'] ?? '',
                    'Usage'         => '1', // Nilai default
                    'Status'        => '4', // Nilai default
                    'Grp Ctr'       => '1', // Nilai default
                    'Operation'     => '',  // Dikosongkan untuk diisi user
                    'Work Ctr'      => '',
                    'Ctrl Key'      => '',
                    'Descriptions'  => '',
                    'Base Qty'      => '',
                    'UoM'           => $parent['uom'] ?? 'PC', // Mengambil dari data BOM
                    'Activity 1'    => '',
                    'UoM 1'         => '',
                    'Activity 2'    => '',
                    'UoM 2'         => '',
                    'Activity 3'    => '',
                    'UoM 3'         => '',
                    'Activity 4'    => '',
                    'UoM 4'         => '',
                    'Activity 5'    => '',
                    'UoM 5'         => '',
                    'Activity 6'    => '',
                    'UoM 6'         => '',
                ];
            }

            if (empty($dataForExport)) {
                 return redirect()->route('bom.index')->withErrors('No valid parent materials found to generate a routing template.');
            }

            $downloadFilename = 'SAP_ROUTING_TEMPLATE_FROM_BOM_' . date('Y-m-d') . '.xlsx';
            return Excel::download(new RoutingTemplateExport($dataForExport), $downloadFilename);

        } catch (\Exception $e) {
            Log::error("Error generating routing template: " . $e->getMessage());
            return redirect()->route('bom.index')->withErrors('Could not generate the routing template due to a server error.');
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
            $descriptionMap = [];

            foreach ($boms as $bom) {
                if (!is_array($bom) || !isset($bom['parent']['code']) || !isset($bom['components']) || !is_array($bom['components'])) continue;

                $parentCode = $bom['parent']['code'];
                if ($parentCode === null || $parentCode === '' || $parentCode === '#NOT_FOUND#') continue;

                // [FIX] Gunakan kode tanpa leading zero sebagai kunci yang konsisten
                $cleanParentCode = ltrim($parentCode, '0');
                $descriptionMap[$cleanParentCode] = $bom['parent']['description'] ?? 'No Description';

                $validComponents = [];
                foreach ($bom['components'] as $comp) {
                    if (!empty($comp['code']) && $comp['code'] !== '#NOT_FOUND#') {
                        $validComponents[] = $comp;
                    }
                }

                if (count($validComponents) === 0) continue;

                $componentsPayload = [];
                foreach ($validComponents as $key => $comp) {
                    $itemNumber = ($key + 1) * 10;
                    $quantity = (float)str_replace(',', '.', $comp['qty'] ?? '0');

                    // === PERUBAHAN LOGIKA PADDING UNTUK KOMPONEN ===
                    $componentCodeForSap = is_numeric($comp['code'])
                                           ? str_pad($comp['code'], 18, '0', STR_PAD_LEFT)
                                           : $comp['code'];

                    $componentsPayload[] = [
                        'ITEM_CATEG'    => 'L', 'POSNR' => str_pad($itemNumber, 4, '0', STR_PAD_LEFT),
                        'COMPONENT'     => $componentCodeForSap,
                        'COMP_QTY'      => $quantity, 'COMP_UNIT' => $comp['uom'] ?? 'PC',
                        'PROD_STOR_LOC' => $comp['sloc'] ?? '', 'SCRAP' => '0',
                        'ITEM_TEXT'     => '', 'ITEM_TEXT2' => '',
                    ];
                }

                $baseQuantity = (float)str_replace(',', '.', $bom['parent']['qty'] ?? '1');
                if ($baseQuantity == floor($baseQuantity)) $baseQuantity = (int)$baseQuantity;

                // === PERUBAHAN LOGIKA PADDING UNTUK MATERIAL HEADER ===
                $parentCodeForSap = is_numeric($parentCode)
                                    ? str_pad($parentCode, 18, '0', STR_PAD_LEFT)
                                    : $parentCode;

                $bomsForUpload[] = [
                    'IV_MATNR'      => $parentCodeForSap,
                    'IV_WERKS'      => $plant, 'IV_STLAN' => '1', 'IV_STLAL' => '01',
                    'IV_DATUV'      => date('dmY'), 'IV_BMENG' => $baseQuantity,
                    'IV_BMEIN'      => $bom['parent']['uom'] ?? 'PC', 'IV_STKTX' => $bom['parent']['description'] ?? 'BOM Upload',
                    'IT_COMPONENTS' => $componentsPayload,
                ];
            }

            if (empty($bomsForUpload)) {
                 return response()->json(['status' => 'success', 'message' => 'BOM upload process finished. No valid BOMs remained.', 'results' => []]);
            }

            Log::info('Final BOM payload being sent to Python API:', ['count' => count($bomsForUpload), 'data' => $bomsForUpload]);
            $response = Http::timeout(600)->post($pythonApiUrl . '/upload_bom', [
                'username' => $request->input('username'), 'password' => $request->input('password'),
                'boms' => $bomsForUpload
            ]);

            $pythonResponse = $response->json();
            if (isset($pythonResponse['results']) && is_array($pythonResponse['results'])) {
                $enrichedResults = [];
                foreach($pythonResponse['results'] as $result) {
                    $matCode = ltrim($result['material_code'], '0');
                    // [FIX] Mencari deskripsi menggunakan kunci yang sudah konsisten
                    $result['description'] = $descriptionMap[$matCode] ?? 'N/A';
                    $enrichedResults[] = $result;
                }
                $pythonResponse['results'] = $enrichedResults;
            }

            return response()->json($pythonResponse);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'A fatal error occurred: ' . $e->getMessage()], 500);
        }
    }

    public function downloadProcessedFile(Request $request, $filename)
    {
        try {
            if (!Storage::disk('local')->exists($filename) || !Str::startsWith($filename, 'bom_processed_')) {
                abort(404, 'File not found or invalid.');
            }

            // File content sekarang sudah bersih karena diperbarui oleh generateBomMaterialCodes
            $fileContent = json_decode(Storage::disk('local')->get($filename), true);
            $boms = $fileContent['boms'] ?? [];
            $plant = $fileContent['plant'] ?? '';

            // Data untuk ekspor sekarang diambil langsung dari file yang telah diproses tanpa modifikasi (ltrim dihapus).
            // Ini memastikan data di file Excel konsisten dengan sumber data.
            $bomsForExport = $boms;

            $downloadFilename = 'SAP_MULTILEVEL_BOM_TEMPLATE_' . date('Y-m-d_H-i') . '.xlsx';
            return Excel::download(new ProcessedBomExport($bomsForExport, $plant), $downloadFilename);

        } catch (\Exception $e) {
            Log::error("Error downloading processed BOM file: " . $e->getMessage());
            return redirect()->route('bom.index')->withErrors('Could not download file.');
        }
    }

    private function findMaterialCode(string $apiUrl, string $description): ?string
    {
        try {
            $originalDescription = trim($description);
            if (empty($originalDescription)) {
                return null;
            }

            // Langkah 1: Coba pencarian tepat dengan data asli (raw)
            $response = Http::timeout(20)->get($apiUrl . '/find_material', ['description' => $originalDescription]);
            if ($response->successful() && $response->json('status') === 'success') {
                $foundCode = $response->json('material_code');
                Log::info("Material '{$originalDescription}' ditemukan dengan pencarian tepat (raw): {$foundCode}");
                return $foundCode;
            }

            // Langkah 2: Jika gagal, bersihkan deskripsi dari spasi ganda dan coba lagi
            $cleanedDescription = preg_replace('/\s+/', ' ', $originalDescription);
            if ($cleanedDescription !== $originalDescription) {
                $response = Http::timeout(20)->get($apiUrl . '/find_material', ['description' => $cleanedDescription]);
                if ($response->successful() && $response->json('status') === 'success') {
                    $foundCode = $response->json('material_code');
                    Log::info("Material '{$originalDescription}' (searched as '{$cleanedDescription}') ditemukan dengan pencarian tepat (cleaned): {$foundCode}");
                    return $foundCode;
                }
            }

            // // Langkah 3: Jika deskripsi lebih dari 40 karakter, coba pencarian terpotong
            // if (strlen($cleanedDescription) > 40) {
            //     $truncatedDescription = substr($cleanedDescription, 0, 39) . '*';
            //     Log::info("Deskripsi terlalu panjang, mencoba pencarian terpotong: '{$truncatedDescription}'");

            //     $response = Http::timeout(15)->get($apiUrl . '/find_material', ['description' => $truncatedDescription]);
            //     if ($response->successful() && $response->json('status') === 'success') {
            //         $foundCode = $response->json('material_code');
            //         Log::info("Material '{$originalDescription}' (searched as '{$truncatedDescription}') ditemukan: {$foundCode}");
            //         return $foundCode;
            //     }
            // }

            // // Langkah 4: Jika masih gagal, gunakan pencarian wildcard penuh
            // Log::warning("Pencarian tepat untuk '{$cleanedDescription}' gagal, mencoba pencarian wildcard penuh.");

            // $tempSearch = str_replace([' ', '-'], '*', $cleanedDescription);
            // $wildcardDescription = '*' . preg_replace('/\*+/', '*', $tempSearch) . '*';

            // $response = Http::timeout(15)->get($apiUrl . '/find_material', ['description' => $wildcardDescription]);
            // if ($response->successful() && $response->json('status') === 'success') {
            //     $foundCode = $response->json('material_code');
            //     Log::info("Material '{$originalDescription}' (searched as '{$wildcardDescription}') ditemukan dengan pencarian wildcard: {$foundCode}");
            //     return $foundCode;
            // }

            Log::warning("Semua metode pencarian untuk '{$originalDescription}' gagal.");
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
        'file' => 'required|mimes:xls,xlsx,csv', 'start_material_code' => 'required|string',
        'material_type' => 'required|string', 'plant' => 'required|string',
        'division' => ['required_if:material_type,FERT', 'nullable', 'string'],
        'distribution_channel' => ['required_if:material_type,FERT', 'nullable', 'string'],
    ]);

    // --- [VALIDASI BARU] Cek kesesuaian Material Type dan awalan Kode Material ---
    $materialType = $request->input('material_type');
    $materialCode = $request->input('start_material_code');

    $rules = [
        'HALB' => ['2', 'H'],
        'HALM' => ['9'],
        'FERT' => ['3'],
        'VERP' => ['5'],
    ];

    if (isset($rules[$materialType])) {
        $isValid = false;
        foreach ($rules[$materialType] as $prefix) {
            if (str_starts_with(strtoupper($materialCode), strtoupper($prefix))) {
                $isValid = true;
                break;
            }
        }
        if (!$isValid) {
            $allowedPrefixes = implode(' atau ', $rules[$materialType]);
            $errorMessage = "Starting Material Code tidak valid. Untuk tipe {$materialType}, kode harus diawali dengan '{$allowedPrefixes}'.";
            return back()->withErrors(['start_material_code' => $errorMessage])->withInput();
        }
    }
    // --- AKHIR VALIDASI BARU ---

    try {
        $collection = Excel::toCollection(null, $request->file('file'))->first();
        if ($collection->isEmpty() || $collection->count() <= 1) {
            return back()->withErrors(['file' => 'File yang Anda upload kosong atau hanya berisi header.']);
        }

        // ... (sisa kode fungsi upload Anda tetap sama seperti sebelumnya) ...

        $inventorHeader = array_map('strtolower', array_map('trim', $collection->first()->toArray()));

        $exactHeaders = [
            'material description', 'base unit of measure', 'document', 'dimension',
            'mrp group', 'mrp controller', 'material group', 'storage location',
            'procurement', 'spec proc'
        ];

        $missingHeaders = array_diff($exactHeaders, $inventorHeader);
        $extraHeaders = array_diff($inventorHeader, $exactHeaders);

        if (!empty($missingHeaders) || !empty($extraHeaders)) {
            $errorParts = [];
            if (!empty($missingHeaders)) {
                $errorParts[] = "Kolom berikut WAJIB ADA: " . implode(', ', $missingHeaders);
            }
            if (!empty($extraHeaders)) {
                $errorParts[] = "Kolom berikut SEHARUSNYA TIDAK ADA: " . implode(', ', $extraHeaders);
            }
            $errorMessage = "Template Excel tidak sesuai. " . implode('; ', $errorParts);
            return back()->withErrors(['file' => $errorMessage]);
        }

        $inventorData = $collection->slice(1);

        $divisionMap = [
            '01' => ['acct_group' => '01', 'val_class' => 'FG01', 'mat_group' => 'FFG001'], '02' => ['acct_group' => '02', 'val_class' => 'FG02', 'mat_group' => 'FFG009'],
            '03' => ['acct_group' => '03', 'val_class' => 'FG06', 'mat_group' => 'FFG008'], '04' => ['acct_group' => '04', 'val_class' => 'FG04', 'mat_group' => 'FFG004'],
            '05' => ['acct_group' => '05', 'val_class' => 'FG03', 'mat_group' => 'FFG002'], '06' => ['acct_group' => '06', 'val_class' => 'FG05', 'mat_group' => 'FFG003'],
            '07' => ['acct_group' => '07', 'val_class' => 'FG07', 'mat_group' => 'FFG007'], '08' => ['acct_group' => '08', 'val_class' => 'FG10', 'mat_group' => 'FFG005'],
            '09' => ['acct_group' => '09', 'val_class' => 'FG01', 'mat_group' => 'FFG001'], '10' => ['acct_group' => '10', 'val_class' => 'FG08', 'mat_group' => 'FFG007'],
            '00' => ['acct_group' => '00', 'val_class' => 'SF01', 'mat_group' => ''],
        ];

        $columnMapping = [
            "Material Description" => "Material Description", "Base Unit of Measure" => "Base Unit of Measure",
            "Dimension" => "Dimension", "MRP GROUP" => "MRP GROUP", "MRP Controller" => "MRP Controller",
            "Material Group" => "Material Group", "Storage Location" => "Storage Location",
            "Production Storage Location" => "Storage Location", "Document" => "Document",
            "Procurement Type" => "Procurement",
            "Special Procurement Type" => "Spec Proc"
        ];

        $sapHeader = [ 'Material', 'Industry Sector', 'Old material number', 'Material Type', 'Material Group', 'Base Unit of Measure', 'Material Description', 'Division', 'General item cat group', 'Prod./insp. Memo', 'Document', 'Ind. Std Desc', 'Dimension', 'Plant', 'Storage Location', 'Sales Organization', 'Distribution Channel', 'Delivery Plant', 'Sales Unit', 'Tax Country', 'Tax Class', 'Tax Cat', 'Item Category Group', 'Acct assignment grp', 'Mat Group 1', 'Mat Group 2', 'Mat Group 3', 'Mat Group 4', 'Mat Group 5', 'Trans Group', 'Loadin Group', 'Material Package', 'Mat pack type', 'Batch Management', 'Profit Center', 'Valuation Class', 'StandardPrc', 'MovingAvg', 'Price Unit', 'Price Control', 'Price Unit Hard Currency', 'Denominator', 'Alternative UoM', 'Numerator', 'Length', 'Width', 'Height', 'Unit of Dimension', 'Gross Weight', 'Weight Unit', 'Net Weight', 'Volume', 'Purchasing Group', 'Volume Unit', 'Proportion unit', 'Class', 'WARNA ', 'VOLUME PRODUCT', 'MRP Type', 'MRP GROUP', 'MRP Controller', 'Lot Size', 'Min Lot Size', 'Max Lot Size', 'Rounding Value', 'Procurement Type', 'Special Procurement Type', 'Backflush Indicator', 'Inhouse Production', 'Pl. Deliv. Time', 'GR Processing Time', 'Schedulled Margin Key', 'Safety Stock', 'Strategy Group', 'Consumption Mode', 'Forward Consumption Period', 'Backward Consumption Period', 'period indicator', 'fiscal year', 'Availability Check', 'Selection Method', 'Individual Collective', 'Unit Of Issue', 'Production Storage Location', 'Storage loc. for EP', 'Prod Schedule Profile', 'Under Delivery Tolerance', 'Over Delivery Tolerance', 'Unlt Deliv Tol', 'Material-related origin', 'Ind Qty Structure', 'costing lot size', 'Do Not Cost', 'Plant-sp.matl status', 'Stock Determination Group', 'Unnamed: 95', 'Inspection Type', 'Inspection With Task List' ];

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
                    $cleanedValue = preg_replace('/\s+/', ' ', trim((string)$rowData[$inventorColLower]));
                    $tempSapRow[$sapCol] = strtoupper($cleanedValue);
                }
            }

            $profitCenterMap = [ '3000' => '300301', '2000' => '200301', '1000' => '100301', '1001' => '100301' ];
            $tempSapRow["Material"] = $currentMaterialCode;
            $currentMaterialCode = $this->incrementMaterialCode($currentMaterialCode);
            $tempSapRow["Material Type"] = $selectedMaterialType; $tempSapRow["Plant"] = $selectedPlant;
            $tempSapRow["Profit Center"] = $profitCenterMap[$selectedPlant] ?? '';
            $tempSapRow["Price Control"] = "S"; $tempSapRow["Industry Sector"] = "F"; $tempSapRow["General item cat group"] = "NORM";
            $tempSapRow["Batch Management"] = "X"; $tempSapRow["Valuation Class"] = "SF01"; $tempSapRow["Price Unit"] = "1";
            $tempSapRow["Class"] = "PRODUCTION"; $tempSapRow["MRP Type"] = "PD"; $tempSapRow["Lot Size"] = "EX";
            $tempSapRow["Backflush Indicator"] = "1"; $tempSapRow["Schedulled Margin Key"] = "000";
            $tempSapRow["Strategy Group"] = "40"; $tempSapRow["period indicator"] = "M"; $tempSapRow["Availability Check"] = "KP";
            $tempSapRow["Individual Collective"] = "1"; $tempSapRow["Prod Schedule Profile"] = "000002"; $tempSapRow["Material-related origin"] = "X";
            $tempSapRow["Ind Qty Structure"] = "X"; $tempSapRow["Plant-sp.matl status"] = "03"; $tempSapRow["Stock Determination Group"] = "0001";
            $tempSapRow["Inspection Type"] = "04"; $tempSapRow["Inspection With Task List"] = "X"; $tempSapRow["Costing lot size"] = "100";

            if (empty($tempSapRow["Procurement Type"])) {
                $tempSapRow["Procurement Type"] = "E";
            }

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

    public function stageMaterials(Request $request)
    {
        $request->validate(['filename' => 'required']);
        try {
            // Nama file sekarang datang dari frontend, bukan dari request asli
            $filename = $request->input('filename');
            if (!Storage::disk('local')->exists($filename)) {
                return response()->json(['status' => 'error', 'message' => 'Processed file not found.'], 404);
            }

            $pythonApiUrl = env('PYTHON_SAP_API_URL', 'http://127.0.0.1:5001');
            $materials = json_decode(Storage::disk('local')->get($filename), true);

            // Panggil endpoint Python yang baru: /stage_materials
            $response = Http::timeout(600)->post($pythonApiUrl . '/stage_materials', [
                'materials' => $materials
            ]);

            // Simpan hasil untuk didownload nanti
            $reportFilename = 'report_' . Str::after($filename, 'material_processed_');
            $responseData = $response->json();
            if($response->successful() && isset($responseData['results'])) {
                Storage::disk('local')->put($reportFilename, json_encode($responseData['results']));
                $request->session()->put('report_filename', $reportFilename);
            }

            // Kembalikan respons dari Python langsung ke frontend
            return $response->json();
        } catch (\Exception $e) {
            Log::error('Staging API Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function download($filename)
    {
        // Pastikan nama file adalah yang benar
        $filePath = $filename;
        if (!Str::startsWith($filename, 'material_processed_')) {
             $filePath = 'material_processed_' . Str::after($filename, 'material_processed_');
        }

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

    public function activateAndUpload(Request $request)
    {
        $request->validate(['username' => 'required', 'password' => 'required', 'materials' => 'required|array']);
        try {
            $pythonApiUrl = env('PYTHON_SAP_API_URL', 'http://127.0.0.1:5001');

            // Panggil endpoint Python yang baru: /activate_qm_and_upload
            // $request->all() sudah berisi username, password, dan materials
            $response = Http::timeout(600)->post($pythonApiUrl . '/activate_qm_and_upload', $request->all());

            // Kembalikan respons dari Python langsung ke frontend
            return $response->json();
        } catch (\Exception $e) {
            Log::error('Activate and Upload API Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // --- START: FUNGSI BARU UNTUK INSPECTION PLAN ---
    public function createInspectionPlan(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
            'materials' => 'required|array',
            'plan_details' => 'required|array',
        ]);
        try {
            $pythonApiUrl = env('PYTHON_SAP_API_URL', 'http://127.0.0.1:5001');

            // Panggil endpoint Python yang baru: /create_inspection_plan
            $response = Http::timeout(600)->post($pythonApiUrl . '/create_inspection_plan', $request->all());

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Create Inspection Plan API Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Error communicating with SAP service: ' . $e->getMessage()], 500);
        }
    }
    // --- END: FUNGSI BARU UNTUK INSPECTION PLAN ---

    public function downloadUploadReport(Request $request)
    {
        try {
            // Ambil dari request POST jika ada, jika tidak, coba dari session
            if ($request->has('results')) {
                $results = $request->input('results');
            } elseif ($request->session()->has('report_filename')) {
                $reportFilename = $request->session()->get('report_filename');
                if (Storage::disk('local')->exists($reportFilename)) {
                    $results = json_decode(Storage::disk('local')->get($reportFilename), true);
                    Storage::disk('local')->delete($reportFilename); // Hapus setelah diambil
                } else {
                    return response()->json(['message' => 'Report file not found in session.'], 404);
                }
            } else {
                 return response()->json(['message' => 'No result data provided for the report.'], 400);
            }

            $plant = $request->input('plant', session('processed_plant', 'N_A'));
            $downloadFilename = 'SAP_UPLOAD_REPORT_' . $plant . '_' . date('Y-m-d') . '.xlsx';

            return Excel::download(new SapUploadReportExport($results), $downloadFilename);
        } catch (\Exception $e) {
            Log::error("Error generating upload report: " . $e->getMessage());
            return response()->json(['message' => 'Could not generate the report due to a server error.'], 500);
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
        $request->validate([
            'recipients'   => 'required|array',
            'recipients.*' => 'required|email',
            'results'      => 'required|array',
            'plant'        => 'required|string',
        ]);

        try {
            $plant = $request->input('plant');
            $resultsWithPlant = array_map(function($result) use ($plant) {
                if (!isset($result['plant'])) {
                    $result['plant'] = $plant;
                }
                return $result;
            }, $request->input('results'));

            Mail::to($request->input('recipients'))->send(new MaterialUploadNotification($resultsWithPlant));

            return response()->json(['message' => 'Email notification sent successfully!']);
        } catch (\Exception $e) {
            Log::error('Email sending failed: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to send email. Server error: ' . $e->getMessage()], 500);
        }
    }

    public function sendBomNotification(Request $request)
    {
        $request->validate([
            'recipients'   => 'required|array',
            'recipients.*' => 'required|email',
            'results'      => 'required|array',
            'plant'        => 'required|string',
        ]);

        try {
            $plant = $request->input('plant');
            $resultsWithPlant = array_map(function($result) use ($plant) {
                if (!isset($result['plant'])) {
                    $result['plant'] = $plant;
                }
                return $result;
            }, $request->input('results'));

            // Menggunakan Mailable khusus BOM
            Mail::to($request->input('recipients'))->send(new SapUploadNotification($resultsWithPlant));

            return response()->json(['message' => 'Email notification sent successfully!']);
        } catch (\Exception $e) {
            Log::error('BOM Email sending failed: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to send email. Server error: ' . $e->getMessage()], 500);
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
