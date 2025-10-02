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

class RoutingController extends Controller
{
    /**
     * Menampilkan halaman utama routing dengan data yang sudah dikelompokkan dari database.
     */
    public function index()
    {
        // Variabel ini akan diteruskan ke view, meskipun mungkin tidak digunakan langsung di JavaScript
        $pythonApiUrl = env('PYTHON_ROUTING_API_URL', 'http://127.0.0.1:5002');

        // Mengambil semua data routing yang belum di-upload, diurutkan untuk konsistensi grup
        $savedData = Routing::whereNull('uploaded_to_sap_at')
                            ->orderBy('document_number')
                            ->orderBy('id')
                            ->get();

        // Mengelompokkan data berdasarkan nomor dokumen
        $groupedByDocument = $savedData->groupBy('document_number');

        $formattedRoutings = [];
        foreach ($groupedByDocument as $docNumber => $routings) {
            $firstRouting = $routings->first();
            if (!$firstRouting) continue; // Lewati jika grup kosong

            $groupData = [];
            foreach ($routings as $routing) {
                // Pastikan 'operations' adalah array
                $operations = is_string($routing->operations) ? json_decode($routing->operations, true) : $routing->operations;
                if (!is_array($operations)) {
                    $operations = []; // Fallback jika data tidak valid
                }

                $groupData[] = [
                    'header' => [
                        'IV_MATERIAL' => $routing->material,
                        'IV_PLANT' => $routing->plant,
                        'IV_DESCRIPTION' => $routing->description,
                    ],
                    'operations' => $operations
                ];
            }

            // FIX: Menambahkan nomor dokumen ke dalam judul header untuk ditampilkan di view
            $formattedRoutings[] = [
                'fileName' => 'Dokumen: ' . $firstRouting->document_name . ' - ' . $firstRouting->product_name . ' (' . $docNumber . ')',
                'document_number' => $docNumber,
                'status' => $firstRouting->status,
                'data' => $groupData,
                'is_saved' => true,
            ];
        }
        return view('routing.index', ['savedRoutings' => $formattedRoutings]);
    }

    /**
     * Memproses file Excel yang diunggah.
     */
    public function processFile(Request $request)
    {
        $request->validate([
            'routing_file' => 'required|mimes:xlsx,xls,csv'
        ]);

        $file = $request->file('routing_file');
        $fileName = $file->getClientOriginalName();

        try {
            $sheetsAsArray = Excel::toArray(new \stdClass(), $file);
            $collection = collect($sheetsAsArray[0]);

            if ($collection->count() < 2) { // Membutuhkan setidaknya header dan satu baris data
                return response()->json(['error' => 'File Excel kosong atau hanya berisi header.'], 422);
            }

            $headers = $collection->first();
            // Membersihkan header dan mengubahnya menjadi snake_case
            $cleanedHeaders = collect($headers)->map(function($cell) {
                if ($cell === null) return null;
                return strtolower(str_replace([' ', '/'], '_', $cell));
            })->filter()->toArray(); // filter() untuk menghapus header null

            $rows = $collection->slice(1);

            $groupedByMaterial = [];

            foreach ($rows as $row) {
                 // Menghapus nilai null dari baris agar sesuai dengan jumlah header
                $filteredRow = collect($row)->filter(function ($value, $key) use ($cleanedHeaders) {
                    return $key < count($cleanedHeaders);
                })->toArray();

                // FIX: Memeriksa apakah jumlah kolom di baris sesuai dengan jumlah header
                if (count($cleanedHeaders) !== count($filteredRow)) {
                    Log::warning('Melewati baris yang jumlah kolomnya tidak cocok.', ['header_count' => count($cleanedHeaders), 'row_count' => count($filteredRow)]);
                    continue; // Lewati baris ini jika jumlah kolom tidak cocok
                }

                // Memeriksa apakah baris sepenuhnya kosong
                if (empty(array_filter($filteredRow))) {
                    continue;
                }

                $rowData = array_combine($cleanedHeaders, $filteredRow);
                $material = $rowData['material'] ?? null;

                if (empty($material)) continue;

                if (!isset($groupedByMaterial[$material])) {
                    $groupedByMaterial[$material] = [
                        'header' => [
                            'IV_MATERIAL' => $material,
                            'IV_PLANT' => $rowData['plant'] ?? null,
                            'IV_DESCRIPTION' => $rowData['description'] ?? null,
                        ],
                        'operations' => []
                    ];
                }

                $operation = [
                    'WORK_CNTR'   => $rowData['work_cntr'] ?? null,
                    'CONTROL_KEY' => $rowData['ctrl_key'] ?? null,
                    'DESCRIPTION' => $rowData['descriptions'] ?? null,
                    'BASE_QTY'    => $rowData['base_qty'] ?? null,
                    'UOM'         => $rowData['uom'] ?? null,
                    'ACTIVITY_1'  => $rowData['activity_1'] ?? null,
                    'UOM_1'       => $rowData['uom_1'] ?? null,
                    'ACTIVITY_2'  => $rowData['activity_2'] ?? null,
                    'UOM_2'       => $rowData['uom_2'] ?? null,
                    'ACTIVITY_3'  => $rowData['activity_3'] ?? null,
                    'UOM_3'       => $rowData['uom_3'] ?? null,
                    'ACTIVITY_4'  => $rowData['activity_4'] ?? null,
                    'UOM_4'       => $rowData['uom_4'] ?? null,
                    'ACTIVITY_5'  => $rowData['activity_5'] ?? null,
                    'UOM_5'       => $rowData['uom_5'] ?? null,
                    'ACTIVITY_6'  => $rowData['activity_6'] ?? null,
                    'UOM_6'       => $rowData['uom_6'] ?? null,
                ];
                $groupedByMaterial[$material]['operations'][] = $operation;
            }

            return response()->json([
                'fileName' => $fileName,
                'data' => array_values($groupedByMaterial),
                'is_saved' => false
            ]);

        } catch (\Exception $e) {
            Log::error('Error processing routing file: ' . $e->getMessage() . ' on line ' . $e->getLine() . ' in file ' . $e->getFile());
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

                // Hapus data material lama jika ada untuk menghindari duplikat di dokumen lain
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
                        'operations' => json_encode($routingData['operations']),
                    ]);
                }
            });

            return response()->json(['status' => 'success', 'message' => 'Data routing berhasil disimpan dengan nomor dokumen baru.']);
        } catch (\Exception $e) {
            Log::error('Gagal menyimpan routing: ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString());
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan di server saat menyimpan. Pesan error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Menandai routing sebagai telah di-upload ke SAP.
     */
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

    /**
     * Menghapus seluruh dokumen routing berdasarkan nomor dokumen.
     */
    public function deleteRoutings(Request $request)
    {
        $validated = $request->validate([
            'document_numbers' => 'required|array'
        ]);

        Routing::whereIn('document_number', $validated['document_numbers'])->delete();

        return response()->json(['status' => 'success', 'message' => 'Dokumen berhasil dihapus.']);
    }

    /**
     * Menghapus baris routing tertentu (material) dari sebuah dokumen.
     */
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

    /**
     * Memperbarui status prioritas sebuah dokumen.
     */
    public function updateStatus(Request $request)
    {
        // FIX: Menyesuaikan aturan validasi dengan nama status yang baru
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
            $errorMessage = "Gagal terhubung ke Python di URL: '{$pythonApiUrl}'. Pastikan service Python berjalan dan URL di file .env sudah benar. Detail: " . $e->getMessage();
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

    /**
     * Helper untuk mendapatkan nomor dokumen berikutnya.
     */
    private function getNextDocumentNumber()
    {
        // [FIX] Menggunakan transaksi database untuk memastikan proses aman (atomic).
        return DB::transaction(function () {
            $sequence = DocumentSequence::where('name', 'routing')->lockForUpdate()->first();

            if (!$sequence) {
                // Jika tidak ada, buat baris baru.
                $sequence = new DocumentSequence();
                $sequence->name = 'routing';
                $sequence->last_number = 0;
                $sequence->save(); // Perintah INSERT
            }

            $nextNumber = $sequence->last_number + 1;

            // [FIX] Melakukan update secara eksplisit menggunakan 'where' untuk menghindari ketergantungan pada kolom 'id'.
            DocumentSequence::where('name', 'routing')->update(['last_number' => $nextNumber]);

            // Format nomor: RPP0000000001
            return 'RPP' . str_pad($nextNumber, 10, '0', STR_PAD_LEFT);
        });
    }
}

