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
                // --- PERBAIKAN DARI ERROR SEBELUMNYA ADA DI SINI ---
                $operations = json_decode($routing->operations, true);
                // Selalu pastikan hasilnya adalah array, bahkan jika hasil decode adalah null atau bukan array
                if (!is_array($operations)) {
                    $operations = [];
                }
                // --- AKHIR PERBAIKAN ---

                $header_data = $routing->header ? json_decode($routing->header, true) : [
                    'IV_MATERIAL' => $routing->material,
                    'IV_PLANT' => $routing->plant,
                    'IV_DESCRIPTION' => $routing->description,
                ];

                $itemData = [
                    'header' => $header_data,
                    'operations' => $operations
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
            $sheetsAsArray = Excel::toArray(new \stdClass(), $file);
            $collection = collect($sheetsAsArray[0]);

            if ($collection->count() < 2) {
                return response()->json(['error' => 'File Excel kosong atau hanya berisi header.'], 422);
            }

            $headers = $collection->first();
            $fileHeaders = collect($headers)->map(fn($h) => trim(strtoupper($h)))->filter();

            $requiredHeaders = collect([
                'MATERIAL', 'PLANT', 'DESCRIPTION', 'USAGE', 'STATUS', 'GRP CTR', 'OPERATION',
                'WORK CNTR', 'CTRL KEY', 'DESCRIPTIONS', 'BASE QTY', 'UOM', 'ACTIVITY 1', 'UOM 1'
            ])->map(fn($h) => strtoupper($h));

            $missingHeaders = $requiredHeaders->diff($fileHeaders);

            if ($missingHeaders->isNotEmpty()) {
                return response()->json(['error' => 'Header tidak sesuai. Header yang hilang: ' . $missingHeaders->implode(', ')], 422);
            }

            $headerIndexMap = $fileHeaders->flip();
            $rows = $collection->slice(1);

            foreach ($rows as $index => $row) {
                if (empty(array_filter($row))) continue;
                $rowNum = $index + 2;
                $material = $row[$headerIndexMap['MATERIAL']] ?? 'N/A';
                $mandatoryCols = ['OPERATION', 'WORK CNTR', 'CTRL KEY'];
                foreach ($mandatoryCols as $colName) {
                    $value = $row[$headerIndexMap[$colName]] ?? null;
                    if (is_null($value) || trim($value) === '') {
                        return response()->json(['error' => "Data tidak valid pada baris {$rowNum} untuk Material '{$material}'. Kolom wajib '{$colName}' tidak boleh kosong."], 422);
                    }
                }
            }

            $cleanedHeaders = $fileHeaders->map(function($cell) {
                return $cell ? strtolower(str_replace([' ', '/'], '_', $cell)) : null;
            })->filter()->toArray();

            $groupedByMaterial = [];

            foreach ($rows as $row) {
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
                    ];
                }

                $operation = [
                    'IV_MATNR'   => (string) ($material),
                    'IV_WERKS'   => (string) ($rowData['plant'] ?? null),
                    // [PERBAIKAN 1] Memastikan Group Counter selalu 2 digit (misal: '1' menjadi '01')
                    'IV_PLNAL'   => str_pad((string)($rowData['grp_ctr'] ?? '1'), 2, '0', STR_PAD_LEFT),
                    'IV_VORNR'   => (string) ($rowData['operation'] ?? null),
                    'IV_ARBPL'   => (string) ($rowData['work_cntr'] ?? null),
                    'IV_STEUS'   => (string) ($rowData['ctrl_key'] ?? null),
                    'IV_LTXA1'   => (string) ($rowData['descriptions'] ?? null),
                    'IV_BMSCHX'  => (string) ($rowData['base_qty']),
                ];

                // [PERBAIKAN 2] Logika untuk mengisi VGE..X dengan 'S' jika VGW..X ada nilainya
                for ($i = 1; $i <= 6; $i++) {
                $activity_key = 'activity_' . $i;
                $uom_key = 'uom_' . $i;

                $activity_val = (string) ($rowData[$activity_key] ?? null);

                if (!empty($activity_val)) {
                    // Ambil nilai UOM dari Excel, jika kosong gunakan default 'PC' atau biarkan kosong sesuai kebutuhan
                    $uom_val = (string) ($rowData[$uom_key] ?? '');

                    $operation['IV_VGW0' . $i . 'X'] = $activity_val;
                    $operation['IV_VGE0' . $i . 'X'] = $uom_val; // Hardcode 'S' jika ada aktivitas
                    }
                }

                $groupedByMaterial[$material]['operations'][] = $operation;
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

    try {
        DB::transaction(function () use ($validated) {
            $documentNumber = $this->getNextDocumentNumber();

            // HAPUS BARIS-BARIS BERMASALAH INI:
            // $materials = collect($validated['routings'])->pluck('header.IV_MATERIAL');
            // Routing::whereIn('material', $materials)->delete();

            // Lanjutkan dengan loop untuk membuat data baru
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
                ]);
            }
        });
        return response()->json(['status' => 'success', 'message' => 'Data routing berhasil disimpan.']);
    } catch (\Exception $e) {
        // Log error untuk debugging
        Log::error('Gagal menyimpan routing: ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString());

        $errorMessage = 'Gagal menyimpan. Terjadi error di server. Silakan cek log.';
        return response()->json(['status' => 'error', 'message' => $errorMessage], 500);
    }
}

    public function markAsUploaded(Request $request)
    {
        $validated = $request->validate([
            'successful_uploads' => 'required|array',
            'successful_uploads.*.material' => 'required|string',
            'successful_uploads.*.doc_number' => 'required|string',
        ]);

        // Ambil daftar unik nomor dokumen dari request
        $documentNumbers = collect($validated['successful_uploads'])->pluck('doc_number')->unique();

        DB::transaction(function () use ($validated) {
            foreach ($validated['successful_uploads'] as $upload) {
                Routing::where('document_number', $upload['doc_number'])
                       ->where('material', $upload['material'])
                       ->whereNull('uploaded_to_sap_at') // Hanya update yang belum ditandai
                       ->update(['uploaded_to_sap_at' => now()]);
            }
        });

        // Setelah transaksi selesai, periksa setiap dokumen untuk pengiriman notifikasi
        foreach ($documentNumbers as $docNumber) {
            $this->checkAndNotifyDocumentCompletion($docNumber);
        }

        return response()->json(['status' => 'success', 'message' => 'Data berhasil ditandai sebagai ter-upload.']);
    }

    /**
     * Memeriksa apakah semua item dalam dokumen sudah diunggah,
     * dan mengirim notifikasi jika sudah lengkap.
     * @param string $docNumber
     */
    private function checkAndNotifyDocumentCompletion(string $docNumber)
    {
        try {
            // Cek apakah notifikasi untuk dokumen ini sudah pernah dikirim
            $notificationAlreadySent = Routing::where('document_number', $docNumber)
                                              ->where('notification_sent', true)
                                              ->exists();
            if ($notificationAlreadySent) {
                return; // Jika sudah pernah dikirim, hentikan proses
            }

            // Hitung total item dan item yang sudah diupload untuk dokumen ini
            $totalItems = Routing::where('document_number', $docNumber)->count();
            $uploadedItems = Routing::where('document_number', $docNumber)->whereNotNull('uploaded_to_sap_at')->count();

            // Jika semua item sudah diupload
            if ($totalItems > 0 && $totalItems === $uploadedItems) {

                // Ambil semua data dari dokumen untuk dimasukkan ke email
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

                // Kirim email notifikasi
                Mail::to('kmi3.60.smg@gmail.com')->send(new RoutingDocumentComplete($documentDetails));

                // Tandai bahwa notifikasi untuk dokumen ini sudah dikirim
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
    public function uploadToSap(Request $request)
    {
        $pythonApiUrl = env('PYTHON_ROUTING_API_URL');
        if (!$pythonApiUrl) {
            return response()->json(['error' => 'PYTHON_ROUTING_API_URL tidak disetel di file .env'], 500);
        }
        try {
            // [LOGGING DITAMBAHKAN DI SINI]
            Log::info('Data to be sent to Python API:', ['routing_data' => $request->routing_data]);

            $response = Http::timeout(300)->post($pythonApiUrl . '/create-routing', [
                'username' => $request->username,
                'password' => $request->password,
                'routing_data' => $request->routing_data,
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

