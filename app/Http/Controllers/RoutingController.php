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

        // --- 2. Ambil Data Histori (Sudah Diunggah) ---
        $historyData = Routing::whereNotNull('uploaded_to_sap_at')
                            ->orderBy('uploaded_to_sap_at', 'desc')
                            ->get();
        $formattedHistoryRoutings = $this->formatRoutingsForView($historyData, true);


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
                $operations = is_string($routing->operations) ? json_decode($routing->operations, true) : [];
                $header_data = $routing->header ? json_decode($routing->header, true) : [
                    'IV_MATERIAL' => $routing->material,
                    'IV_PLANT' => $routing->plant,
                    'IV_DESCRIPTION' => $routing->description,
                ];

                $groupData[] = [
                    'header' => $header_data,
                    'operations' => $operations
                ];
            }

            $formattedRoutings[] = [
                'fileName' => 'Dokumen: ' . $firstRouting->document_name . ' - ' . $firstRouting->product_name . ' (' . $docNumber . ')',
                'document_number' => $docNumber,
                'status' => $firstRouting->status,
                'uploaded_at' => $isHistory && $firstRouting->uploaded_to_sap_at ? Carbon::parse($firstRouting->uploaded_to_sap_at)->format('d M Y, H:i') : null,
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
            $cleanedHeaders = collect($headers)->map(function($cell) {
                return $cell ? strtolower(str_replace([' ', '/'], '_', $cell)) : null;
            })->filter()->toArray();

            $rows = $collection->slice(1);
            $groupedByMaterial = [];

            foreach ($rows as $row) {
                $filteredRow = array_slice($row, 0, count($cleanedHeaders));
                if (count($cleanedHeaders) !== count($filteredRow) || empty(array_filter($filteredRow))) {
                    continue;
                }

                $rowData = array_combine($cleanedHeaders, $filteredRow);
                $material = $rowData['material'] ?? null;
                if (empty($material)) continue;

                if (!isset($groupedByMaterial[$material])) {
                    $groupedByMaterial[$material] = [
                        'header' => [
                            'IV_MATERIAL' => (string) ($rowData['material'] ?? null),
                            'IV_PLANT' => (string) ($rowData['plant'] ?? null),
                            'IV_DESCRIPTION' => (string) ($rowData['description'] ?? null),
                            'IV_TASK_LIST_USAGE' => (string) ($rowData['usage'] ?? null),
                            'IV_TASK_LIST_STATUS' => (string) ($rowData['status'] ?? null),
                            'IV_GROUP_COUNTER' => '1',
                            'IV_TASK_MEASURE_UNIT' => (string) $rowData['uom'] ?? null,
                        ],
                        'operations' => []
                    ];
                }

                $operation = [
                    'IV_MATNR'   => (string) ($material),
                    'IV_WERKS'   => (string) ($rowData['plant'] ?? null),
                    'IV_PLNAL'   => (string) ($rowData['grp_ctr'] ?? null),
                    'IV_VORNR'   => (string) ($rowData['operation'] ?? null),
                    'IV_ARBPL'   => (string) ($rowData['work_cntr'] ?? null),
                    'IV_STEUS'   => (string) ($rowData['ctrl_key'] ?? null),
                    'IV_LTXA1'   => (string) ($rowData['descriptions'] ?? null),
                    'IV_BMSCHX'  => (string) ($rowData['base_qty'] ?? null),
                    'IV_VGW01X'  => (string) ($rowData['activity_1'] ?? null),
                    'IV_VGE01X'  => (string) ($rowData['uom_1'] ?? null),
                    'IV_VGW02X'  => (string) ($rowData['activity_2'] ?? null),
                    'IV_VGE02X'  => (string) ($rowData['uom_2'] ?? null),
                    'IV_VGW03X'  => (string) ($rowData['activity_3'] ?? null),
                    'IV_VGE03X'  => (string) ($rowData['uom_3'] ?? null),
                    'IV_VGW04X'  => (string) ($rowData['activity_4'] ?? null),
                    'IV_VGE04X'  => (string) ($rowData['uom_4'] ?? null),
                    'IV_VGW05X'  => (string) ($rowData['activity_5'] ?? null),
                    'IV_VGE05X'  => (string) ($rowData['uom_5'] ?? null),
                    'IV_VGW06X'  => (string) ($rowData['activity_6'] ?? null),
                    'IV_VGE06X'  => (string) ($rowData['uom_6'] ?? null),
                ];
                $groupedByMaterial[$material]['operations'][] = $operation;
            }

            if (empty($groupedByMaterial)) {
                return response()->json(['error' => 'Tidak ada data valid yang ditemukan. Periksa nama kolom "Material".'], 422);
            }

            return response()->json(['fileName' => $fileName, 'data' => array_values($groupedByMaterial), 'is_saved' => false]);
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
                $materials = collect($validated['routings'])->pluck('header.IV_MATERIAL');
                Routing::whereIn('material', $materials)->delete();

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
            Log::error('Gagal menyimpan routing: ' . $e->getMessage());
            $errorMessage = 'Gagal menyimpan. Error dari server: ' . $e->getMessage();
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

        foreach ($validated['successful_uploads'] as $upload) {
            Routing::where('document_number', $upload['doc_number'])
                   ->where('material', $upload['material'])
                   ->update(['uploaded_to_sap_at' => now()]);
        }

        return response()->json(['status' => 'success', 'message' => 'Data berhasil ditandai sebagai ter-upload.']);
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
            $response = Http::timeout(300)->post($pythonApiUrl . '/create-routing', [
                'username' => $request->username,
                'password' => $request->password,
                'routing_data' => $request->routing_data,
            ]);
            return response()->json($response->json(), $response->status());
        } catch (ConnectionException $e) {
            $errorMessage = "Gagal terhubung ke Python: " . $e->getMessage();
            return response()->json(['error' => $errorMessage], 500);
        } catch (\Exception $e) {
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
                // Jika baris sudah ada, naikkan nomor urutnya
                $sequence->increment('last_number');
            } else {
                // Jika belum ada, buat baris baru dengan nomor urut pertama (1)
                $sequence = DocumentSequence::create([
                    'name' => 'routing',
                    'last_number' => 1
                ]);
            }

            // Ambil nomor terbaru dari instance model
            $nextNumber = $sequence->last_number;

            return 'RPP' . str_pad($nextNumber, 10, '0', STR_PAD_LEFT);
        });
    }
}

