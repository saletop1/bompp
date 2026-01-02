<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Client\ConnectionException;
use App\Models\Routing;
use App\Models\DocumentSequence;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Mail\RoutingDocumentComplete;
use PhpOffice\PhpSpreadsheet\IOFactory;

class RoutingController extends Controller
{
    /**
     * Menampilkan halaman utama routing dengan data yang belum dan sudah diunggah.
     */
    public function index()
    {
        // --- 1. Ambil Data yang Belum Diunggah (Pending) ---
        $pendingData = Routing::whereNull('uploaded_to_sap_at')
                            ->orderBy('document_number')
                            ->orderBy('id')
                            ->get();
        $formattedPendingRoutings = $this->formatRoutingsForView($pendingData);

        // --- 2. Ambil Data Histori (Termasuk yang belum diupload dari dokumen yang sama) ---
        $historyDocs = Routing::whereIn('document_number', function($query){
            $query->select('document_number')->from('routings')->whereNotNull('uploaded_to_sap_at');
        })->orderBy('uploaded_to_sap_at', 'desc')->get();

        $formattedHistoryRoutings = $this->formatRoutingsForView($historyDocs, true);


        return view('routing.index', [
            'savedRoutings' => $formattedPendingRoutings,
            'historyRoutings' => $formattedHistoryRoutings
        ]);
    }

    /**
     * Helper function untuk memformat data routing untuk ditampilkan di view.
     * @param \Illuminate\Database\Eloquent\Collection $data
     * @param bool $isHistory
     * @return array
     */
    private function formatRoutingsForView($data, $isHistory = false)
    {
        $groupedByDocument = $data->groupBy('document_number');
        $formattedRoutings = [];

        foreach ($groupedByDocument as $docNumber => $routings) {
            $firstRouting = $routings->first();
            if (!$firstRouting) continue;

            $groupData = [];
            foreach ($routings as $routing) {
                $operations = json_decode($routing->operations, true);
                if (!is_array($operations)) {
                    $operations = [];
                }

                $services = json_decode($routing->services, true);
                if(!is_array($services)){
                    $services = [];
                }

                $header_data = $routing->header ? json_decode($routing->header, true) : [
                    'IV_MATERIAL' => $routing->material,
                    'IV_PLANT' => $routing->plant,
                    'IV_DESCRIPTION' => $routing->description,
                ];

                $itemData = [
                    'header' => $header_data,
                    'operations' => $operations,
                    'services' => $services
                ];

                if ($isHistory) {
                    $itemData['uploaded_at_item'] = $routing->uploaded_to_sap_at
                        ? Carbon::parse($routing->uploaded_to_sap_at)->timezone('Asia/Jakarta')->format('d M Y, H:i')
                        : 'Pending';
                }

                $groupData[] = $itemData;
            }

            $uploadTimestamp = null;
            if ($isHistory) {
                $latestRouting = $routings->whereNotNull('uploaded_to_sap_at')->sortByDesc('uploaded_to_sap_at')->first();
                if ($latestRouting && $latestRouting->uploaded_to_sap_at) {
                     $uploadTimestamp = Carbon::parse($latestRouting->uploaded_to_sap_at)->timezone('Asia/Jakarta')->format('d M Y, H:i');
                }
            }


            $formattedRoutings[] = [
                'fileName' => 'Dokumen: ' . $firstRouting->document_name . ' - ' . $firstRouting->product_name . ' (' . $docNumber . ')',
                'document_number' => $docNumber,
                'status' => $firstRouting->status,
                'uploaded_at' => $uploadTimestamp,
                'data' => $groupData,
                'is_saved' => true,
            ];
        }
        return $formattedRoutings;
    }


    /**
     * Memproses file Excel yang diunggah.
     */
    public function processFile(Request $request)
    {
        $request->validate(['routing_file' => 'required|mimes:xlsx,xls,csv']);
        $file = $request->file('routing_file');
        $fileName = $file->getClientOriginalName();

        try {
            try {
                $spreadsheet = IOFactory::load($file->getRealPath());
                $worksheet = $spreadsheet->getActiveSheet();
                foreach ($worksheet->getRowIterator() as $row) {
                    $cellIterator = $row->getCellIterator();
                    $cellIterator->setIterateOnlyExistingCells(FALSE);
                    foreach ($cellIterator as $cell) {
                        if ($cell->isFormula()) {
                            $cellCoordinate = $cell->getCoordinate();
                            return response()->json(['error' => "File ditolak. Ditemukan formula pada sel {$cellCoordinate}. Harap unggah file yang hanya berisi nilai (values)."], 422);
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::error('Gagal saat validasi formula Excel: ' . $e->getMessage());
                return response()->json(['error' => 'Gagal memvalidasi file Excel. Pastikan file tidak rusak.'], 422);
            }

            $sheetsAsArray = Excel::toArray(new \stdClass(), $file);
            $collection = collect($sheetsAsArray[0]);

            if ($collection->count() < 2) {
                return response()->json(['error' => 'File Excel kosong atau hanya berisi header.'], 422);
            }

            $headers = $collection->first();
            $fileHeaders = collect($headers)->map(fn($h) => trim(strtoupper($h)))->filter();

            $requiredHeaders = collect(['MATERIAL', 'PLANT', 'DESCRIPTION']);
            $missingHeaders = $requiredHeaders->diff($fileHeaders);
            if ($missingHeaders->isNotEmpty()) {
                return response()->json(['error' => 'Header tidak sesuai. Header wajib yang hilang: ' . $missingHeaders->implode(', ')], 422);
            }

            $cleanedHeaders = $fileHeaders->map(function($cell) {
                return $cell ? strtolower(str_replace([' ', '/', '.'], '_', $cell)) : null;
            })->filter()->toArray();

            $rows = $collection->slice(1);
            $groupedByMaterial = [];

            $serviceHeaders = ['purchasing_group', 'pln_deliv_time', 'price_unit', 'net_price', 'currency', 'cost_element', 'mat_grp'];

            foreach ($rows as $index => $row) {
                $rowNum = $index + 2;
                $filteredRow = array_slice($row, 0, count($cleanedHeaders));
                if (empty(array_filter($filteredRow))) continue;

                $rowData = array_combine($cleanedHeaders, $filteredRow);
                $material = $rowData['material'] ?? null;
                if (empty($material)) continue;

                if (!isset($groupedByMaterial[$material])) {
                    $groupedByMaterial[$material] = [
                        'header' => [
                            'IV_MATERIAL' => (string) ($rowData['material'] ?? null),
                            'IV_PLANT' => (string) ($rowData['plant'] ?? null),
                            'IV_DESCRIPTION' => (string) ($rowData['description'] ?? null),
                            'IV_TASK_LIST_USAGE' => (string) ($rowData['usage'] ?? '1'),
                            'IV_TASK_LIST_STATUS' => (string) ($rowData['status'] ?? '4'),
                            'IV_GROUP_COUNTER' => '1',
                            'IV_TASK_MEASURE_UNIT' => (string) ($rowData['uom'] ?? ''),
                        ],
                        'operations' => [],
                        'services' => [],
                    ];
                }

                $ctrlKey = strtoupper(trim($rowData['ctrl_key'] ?? ''));

                if ($ctrlKey === 'ZP02') {
                    $errors = [];

                    if (!empty($rowData['work_cntr'])) { $errors[] = "Work Cntr harus kosong"; }
                    for ($i = 1; $i <= 6; $i++) {
                        if (!empty($rowData['activity_' . $i] ?? null)) { $errors[] = "Activity {$i} harus kosong"; }
                        if (!empty($rowData['uom_' . $i] ?? null)) { $errors[] = "UoM {$i} harus kosong"; }
                    }

                    $serviceValidations = [
                        'purchasing_group' => 'K04',
                        'cost_element' => '5203000001',
                        'mat_grp' => 'DA01'
                    ];

                    foreach($serviceValidations as $key => $expectedValue) {
                        $actualValue = strtoupper(trim($rowData[$key] ?? ''));
                        if ($actualValue !== $expectedValue) {
                            $errors[] = "Kolom '{$key}' harus '{$expectedValue}', tetapi ditemukan '{$actualValue}'";
                        }
                    }

                    if (!empty($errors)) {
                        $errorString = "Validasi gagal untuk baris Jasa (baris excel {$rowNum}): " . implode(', ', $errors) . ".";
                        return response()->json(['error' => $errorString], 422);
                    }

                    $serviceData = [];
                    foreach($serviceHeaders as $key) {
                        $serviceData[$key] = $rowData[$key] ?? null;
                    }
                    $groupedByMaterial[$material]['services'][] = $serviceData;

                } else {
                    // [VALIDASI BARU UNTUK OPERASI STANDAR]
                    $operationErrors = [];

                    // Ambil nilai operation untuk divalidasi
                    $operationValue = (string) ($rowData['operation'] ?? '');

                    // --- Validasi Kolom Operation ---
                    if (empty($operationValue)) {
                        $operationErrors[] = 'Kolom Operation tidak boleh kosong';
                    }
                    // [VALIDASI] Periksa apakah diawali dengan '0'
                    elseif (substr($operationValue, 0, 1) !== '0') {
                        $operationErrors[] = "Kolom Operation ('{$operationValue}') harus diawali dengan angka 0";
                    }
                    // --- Akhir Validasi Operation ---

                    // Validasi kolom wajib lainnya untuk operasi standar
                    if (empty($rowData['work_cntr'])) { $operationErrors[] = 'Kolom Work Cntr tidak boleh kosong'; }
                    if (empty($ctrlKey)) { $operationErrors[] = 'Kolom Ctrl Key tidak boleh kosong'; }

                    // [VALIDASI] Memeriksa semua kolom activity dan uom
                    $allowedUoms = ['S', 'MIN']; // Satuan yang diizinkan

                    for ($i = 1; $i <= 6; $i++) {
                        $activityValue = $rowData['activity_' . $i] ?? null;
                        $uomValue = $rowData['uom_' . $i] ?? null;

                        // Validasi Activity (tidak boleh kosong)
                        if (empty($activityValue)) {
                            $operationErrors[] = 'Kolom Activity ' . $i . ' tidak boleh kosong';
                        }

                        // Validasi UoM (tidak boleh kosong DAN harus 'S' atau 'MIN')
                        if (empty($uomValue)) {
                            $operationErrors[] = 'Kolom UoM ' . $i . ' tidak boleh kosong';
                        } else {
                            // [VALIDASI BARU] Jika UoM tidak kosong, periksa nilainya
                            $uomValueUpper = strtoupper(trim($uomValue));
                            if (!in_array($uomValueUpper, $allowedUoms)) {
                                $operationErrors[] = "Kolom UoM {$i} ('{$uomValue}') harus 'S' atau 'MIN'";
                            }
                        }
                    }


                    // Jika ada error validasi, kumpulkan semua pesan dan reject filenya
                    if (!empty($operationErrors)) {
                        // Pesan error ini sekarang akan menampilkan semua masalah yang ditemukan di baris tersebut
                        $errorString = "Validasi gagal untuk baris Operasi (baris excel {$rowNum}): " . implode(', ', $operationErrors) . ".";
                        return response()->json(['error' => $errorString], 422);
                    }

                    // Jika validasi Operasi berhasil, proses sebagai data operasi
                    $operation = [
                        'IV_MATNR'   => (string) ($material),
                        'IV_WERKS'   => (string) ($rowData['plant'] ?? null),
                        'IV_PLNAL'   => str_pad((string)($rowData['grp_ctr'] ?? '1'), 2, '0', STR_PAD_LEFT),
                        'IV_VORNR'   => $operationValue, // Gunakan $operationValue yang sudah diambil
                        'IV_ARBPL'   => (string) ($rowData['work_cntr'] ?? null),
                        'IV_STEUS'   => (string) ($ctrlKey),
                        'IV_LTXA1'   => (string) ($rowData['descriptions'] ?? null),
                        'IV_BMSCHX'  => (string) ($rowData['base_qty'] ?? null),
                    ];
                    for ($i = 1; $i <= 6; $i++) {
                        $activity_key = 'activity_' . $i;
                        $uom_key = 'uom_' . $i;
                        $activity_val = (string) ($rowData[$activity_key] ?? null);
                        if (!empty($activity_val)) {
                            // Ambil UoM yang sudah divalidasi dan di-uppercase
                            $uom_val = strtoupper(trim((string) ($rowData[$uom_key] ?? '')));
                            $operation['IV_VGW0' . $i . 'X'] = $activity_val;
                            $operation['IV_VGE0' . $i . 'X'] = $uom_val;
                        }
                    }
                    $groupedByMaterial[$material]['operations'][] = $operation;
                }
            }

            if (empty($groupedByMaterial)) {
                return response()->json(['error' => 'Tidak ada data valid yang ditemukan. Periksa nama kolom "Material".'], 422);
            }

            $finalData = array_values($groupedByMaterial);
            return response()->json(['fileName' => $fileName, 'data' => $finalData, 'is_saved' => false]);

        } catch (\Exception $e) {
            Log::error('Error processing routing file: ' . $e->getMessage());
            return response()->json(['error' => 'Gagal memproses file. Pesan: ' . $e->getMessage()], 422);
        }
    }

    /**
     * Menyimpan data routing yang dipilih ke database.
     */
    public function saveRoutings(Request $request)
    {
        $validated = $request->validate([
            'routings' => 'required|array',
            'document_name' => 'required|string|max:40',
            'product_name' => 'required|string|max:20',
        ]);

        $materialsToSave = collect($validated['routings'])->pluck('header.IV_MATERIAL');
        $existingRouting = Routing::whereIn('material', $materialsToSave)->whereNull('uploaded_to_sap_at')->first();

        if ($existingRouting) {
            $errorMessage = "Gagal menyimpan. Material '{$existingRouting->material}' sudah ada di dokumen '{$existingRouting->document_name}' ({$existingRouting->document_number}) dengan status Menunggu.";
            return response()->json(['message' => $errorMessage], 409);
        }

        try {
            DB::transaction(function () use ($validated) {
                $documentNumber = $this->getNextDocumentNumber();
                foreach ($validated['routings'] as $routingData) {
                    Routing::create([
                        'document_number' => $documentNumber,
                        'document_name' => $validated['document_name'],
                        'product_name' => $validated['product_name'],
                        'material' => $routingData['header']['IV_MATERIAL'],
                        'plant' => $routingData['header']['IV_PLANT'],
                        'description' => $routingData['header']['IV_DESCRIPTION'],
                        'header' => json_encode($routingData['header']),
                        'operations' => json_encode($routingData['operations']),
                        'services' => isset($routingData['services']) ? json_encode($routingData['services']) : json_encode([]),
                    ]);
                }
            });
            return response()->json(['status' => 'success', 'message' => 'Data routing berhasil disimpan.']);
        } catch (\Exception $e) {
            Log::error('Gagal menyimpan routing: ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString());
            $errorMessage = 'Gagal menyimpan. Terjadi error di server. Silakan cek log.';
            return response()->json(['status' => 'error', 'message' => $errorMessage], 500);
        }
    }

    /**
     * Format material jika hanya terdiri dari angka (numeric)
     * menjadi 18 karakter dengan leading zero
     */
    private function formatMaterialIfNumeric($material)
    {
        // Hapus whitespace
        $material = trim($material);

        // Jika material hanya terdiri dari angka
        if (ctype_digit($material)) {
            // Tambahkan leading zero hingga panjang 18 karakter
            return str_pad($material, 18, '0', STR_PAD_LEFT);
        }

        // Jika mengandung huruf, kembalikan aslinya
        return $material;
    }

    public function uploadToSap(Request $request)
    {
        $pythonApiUrlBase = env('PYTHON_ROUTING_API_URL');
        if (!$pythonApiUrlBase) {
            return response()->json(['error' => 'PYTHON_ROUTING_API_URL tidak disetel di file .env'], 500);
        }

        $routingData = $request->input('routing_data', []);

        // Format material menjadi 18 digit dengan leading zero jika numerik
        if (isset($routingData['header']['IV_MATERIAL'])) {
            $routingData['header']['IV_MATERIAL'] = $this->formatMaterialIfNumeric($routingData['header']['IV_MATERIAL']);
        }

        // Format material di setiap operasi jika ada
        if (isset($routingData['operations']) && is_array($routingData['operations'])) {
            foreach ($routingData['operations'] as $index => $operation) {
                if (isset($operation['IV_MATNR'])) {
                    $routingData['operations'][$index]['IV_MATNR'] = $this->formatMaterialIfNumeric($operation['IV_MATNR']);
                }
            }
        }

        // Tentukan endpoint berdasarkan isi data
        $isServiceOnly = !empty($routingData['services']) && empty($routingData['operations']);

        $endpoint = $isServiceOnly ? '/create-routing-jasa' : '/create-routing';
        $fullApiUrl = $pythonApiUrlBase . $endpoint;

        try {
            Log::info("Mengirim data ke Python API endpoint: {$fullApiUrl}", ['routing_data' => $routingData]);

            $response = Http::timeout(300)->post($fullApiUrl, [
                'username' => $request->username,
                'password' => $request->password,
                'routing_data' => $routingData,
            ]);

            return response()->json($response->json(), $response->status());
        } catch (ConnectionException $e) {
            $errorMessage = "Gagal terhubung ke Python: " . $e->getMessage();
            Log::error($errorMessage);
            return response()->json(['error' => $errorMessage], 500);
        } catch (\Exception $e) {
            Log::error('Unexpected error in uploadToSap: ' . $e->getMessage());
            return response()->json(['error' => 'Terjadi error tak terduga: ' . $e->getMessage()], 500);
        }
    }

    public function markAsUploaded(Request $request)
    {
        $validated = $request->validate([
            'successful_uploads' => 'required|array',
            'successful_uploads.*.material' => 'required|string',
            'successful_uploads.*.doc_number' => 'required|string',
        ]);

        $documentNumbers = collect($validated['successful_uploads'])->pluck('doc_number')->unique();

        DB::transaction(function () use ($validated) {
            foreach ($validated['successful_uploads'] as $upload) {
                Routing::where('document_number', $upload['doc_number'])
                       ->where('material', $upload['material'])
                       ->whereNull('uploaded_to_sap_at')
                       ->update(['uploaded_to_sap_at' => now()]);
            }
        });

        foreach ($documentNumbers as $docNumber) {
            $this->checkAndNotifyDocumentCompletion($docNumber);
        }

        return response()->json(['status' => 'success', 'message' => 'Data berhasil ditandai sebagai ter-upload.']);
    }

    private function checkAndNotifyDocumentCompletion(string $docNumber)
    {
        try {
            $notificationAlreadySent = Routing::where('document_number', $docNumber)
                                              ->where('notification_sent', true)
                                              ->exists();
            if ($notificationAlreadySent) {
                return;
            }

            $totalItems = Routing::where('document_number', $docNumber)->count();
            $uploadedItems = Routing::where('document_number', $docNumber)->whereNotNull('uploaded_to_sap_at')->count();

            if ($totalItems > 0 && $totalItems === $uploadedItems) {

                $documentRoutings = Routing::where('document_number', $docNumber)->get();
                $firstItem = $documentRoutings->first();

                $documentDetails = [
                    'document_name' => $firstItem->document_name,
                    'product_name' => $firstItem->product_name,
                    'document_number' => $firstItem->document_number,
                    'completion_time' => Carbon::now('Asia/Jakarta')->format('d M Y, H:i:s'),
                    'items' => $documentRoutings->map(function ($item) {
                        return [
                            'material' => $item->material,
                            'description' => $item->description,
                        ];
                    })->all(),
                ];

                Mail::to('costing7.kmi@gmail.com')->send(new RoutingDocumentComplete($documentDetails));

                Routing::where('document_number', $docNumber)->update(['notification_sent' => true]);
                Log::info('Notifikasi email berhasil dikirim untuk dokumen routing: ' . $docNumber);
            }

        } catch (\Exception $e) {
            Log::error('Gagal mengirim notifikasi email untuk dokumen ' . $docNumber . ': ' . $e->getMessage());
        }
    }
    public function deleteRoutings(Request $request)
    {
        $validated = $request->validate(['document_numbers' => 'required|array']);
        Routing::whereIn('document_number', $validated['document_numbers'])->delete();
        return response()->json(['status' => 'success', 'message' => 'Dokumen berhasil dihapus.']);
    }
    public function deleteRoutingRows(Request $request)
    {
        $validated = $request->validate([
            'rows_to_delete' => 'required|array',
            'rows_to_delete.*.doc_number' => 'required|string',
            'rows_to_delete.*.material' => 'required|string',
        ]);
        foreach ($validated['rows_to_delete'] as $row) {
            Routing::where('document_number', $row['doc_number'])
                   ->where('material', $row['material'])
                   ->delete();
        }
        return response()->json(['status' => 'success', 'message' => 'Baris yang dipilih berhasil dihapus.']);
    }
    public function updateStatus(Request $request)
    {
        $validated = $request->validate([
            'document_number' => 'required|string',
            'status' => 'nullable|string|in:Urgent,Priority,Standart,',
        ]);
        Routing::where('document_number', $validated['document_number'])
               ->update(['status' => $validated['status']]);
        return response()->json(['status' => 'success', 'message' => 'Status dokumen berhasil diperbarui.']);
    }

    public function checkDocumentNameExists(Request $request)
    {
        $request->validate(['document_name' => 'required|string']);
        $exists = Routing::where('document_name', $request->input('document_name'))->exists();
        return response()->json(['exists' => $exists]);
    }
    public function checkMaterialsInExistingDocument(Request $request)
    {
        $request->validate(['materials' => 'required|array']);
        $materials = $request->input('materials');
        $existingRouting = Routing::whereIn('material', $materials)->whereNull('uploaded_to_sap_at')->first();
        if ($existingRouting) {
            return response()->json([
                'exists' => true, 'document_name' => $existingRouting->document_name,
                'document_number' => $existingRouting->document_number,
                'material' => $existingRouting->material
            ]);
        }
        return response()->json(['exists' => false]);
    }
    private function getNextDocumentNumber()
    {
        return DB::transaction(function () {
            $sequence = DocumentSequence::where('name', 'routing')->lockForUpdate()->first();
            if ($sequence) {
                $sequence->increment('last_number');
            } else {
                $sequence = DocumentSequence::create([
                    'name' => 'routing',
                    'last_number' => 1
                ]);
            }
            $nextNumber = $sequence->last_number;
            return 'RPP' . str_pad($nextNumber, 10, '0', STR_PAD_LEFT);
        });
    }
}
