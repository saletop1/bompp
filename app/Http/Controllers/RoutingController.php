<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Client\ConnectionException;
use App\Models\Routing;
use App\Models\DocumentSequence;
use Illuminate\Support\Facades\DB;

class RoutingController extends Controller
{
    public function index()
    {
        // [UBAH] Tambahkan ->whereNull('uploaded_to_sap_at')
        // Ini akan membuat controller HANYA mengambil data yang belum pernah di-upload ke SAP.
        $savedData = Routing::whereNull('uploaded_to_sap_at')
                            ->orderBy('created_at', 'desc')
                            ->get();

        $groupedByDocument = $savedData->groupBy('document_number');

        $formattedRoutings = [];
        foreach ($groupedByDocument as $docNumber => $routings) {
            $groupData = [];
            $firstRouting = $routings->first();
            $docName = $firstRouting->document_name;
            $prodName = $firstRouting->product_name;

            foreach ($routings as $routing) {
                $groupData[] = [
                    'header' => [
                        'IV_MATERIAL' => $routing->material,
                        'IV_PLANT' => $routing->plant,
                        'IV_DESCRIPTION' => $routing->description,
                        'IV_TASK_LIST_USAGE' => '1',
                        'IV_TASK_LIST_STATUS' => '4',
                        'IV_GROUP_COUNTER' => '1',
                        'IV_TASK_MEASURE_UNIT' => 'PC',
                    ],
                    'operations' => $routing->operations
                ];
            }

            $headerTitle = 'Dokumen Tersimpan: ' . $docNumber;
            if ($docName) {
                $headerTitle = 'Dokumen: ' . $docName . ' (' . $prodName . ') - ' . $docNumber;
            }

            $formattedRoutings[] = [
                'fileName' => $headerTitle,
                'data' => $groupData
            ];
        }

        return view('routing', ['savedRoutings' => $formattedRoutings]);
    }

    // [TAMBAHKAN FUNGSI BARU INI]
    /**
     * Menandai routing sebagai "sudah di-upload" di database.
     */
    public function markAsUploaded(Request $request)
    {
        $request->validate([
            'successful_uploads' => 'required|array',
            'successful_uploads.*.material' => 'required|string',
            'successful_uploads.*.doc_number' => 'required|string',
        ]);

        $successfulUploads = $request->input('successful_uploads');
        $now = now();

        foreach ($successfulUploads as $upload) {
            Routing::where('document_number', $upload['doc_number'])
                   ->where('material', $upload['material'])
                   ->whereNull('uploaded_to_sap_at') // Pastikan hanya update yang belum ditandai
                   ->update(['uploaded_to_sap_at' => $now]);
        }

        return response()->json(['status' => 'success', 'message' => 'Data berhasil ditandai sebagai ter-upload.']);
    }

    // Fungsi lain di bawah ini tidak perlu diubah (saveRoutings, getNextDocumentNumber, dll.)

    public function saveRoutings(Request $request)
    {
        $request->validate([
            'routings' => 'required|array',
            'document_name' => 'required|string|max:255',
            'product_name' => 'required|string|max:255',
        ]);

        try {
            $docName = $request->input('document_name');
            $productName = $request->input('product_name');

            $docNumber = $this->getNextDocumentNumber('RPP');
            $routingsData = $request->input('routings');
            $insertData = [];
            $now = now();

            foreach ($routingsData as $routing) {
                $insertData[] = [
                    'document_number' => $docNumber,
                    'document_name' => $docName,
                    'product_name' => $productName,
                    'material' => $routing['header']['IV_MATERIAL'],
                    'plant' => $routing['header']['IV_PLANT'],
                    'description' => $routing['header']['IV_DESCRIPTION'],
                    'operations' => json_encode($routing['operations']),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            Routing::insert($insertData);

            return response()->json([
                'status' => 'success',
                'message' => 'Data berhasil disimpan dengan nomor dokumen: ' . $docNumber
            ]);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Gagal menyimpan data: ' . $e->getMessage()], 500);
        }
    }

    private function getNextDocumentNumber(string $prefix): string
    {
        $documentNumber = DB::transaction(function () use ($prefix) {
            $sequence = DocumentSequence::where('prefix', $prefix)->lockForUpdate()->first();
            if ($sequence) {
                $sequence->last_sequence += 1;
                $sequence->save();
                return $sequence->last_sequence;
            } else {
                $newSequence = DocumentSequence::create([
                    'prefix' => $prefix,
                    'last_sequence' => 1
                ]);
                return $newSequence->last_sequence;
            }
        });
        return $prefix . '.' . str_pad($documentNumber, 9, '0', STR_PAD_LEFT);
    }

    public function processFile(Request $request)
    {
        $request->validate(['routing_file' => 'required|mimes:xlsx,xls,csv']);
        try {
            $file = $request->file('routing_file');
            $originalFileName = $file->getClientOriginalName();
            $rows = Excel::toArray(new \stdClass(), $file)[0];
            $header = array_map('trim', array_shift($rows));
            $groupedData = [];
            $currentMaterial = null;
            foreach ($rows as $row) {
                if (empty(array_filter($row))) continue;
                $rowData = array_combine($header, $row);
                if (!empty($rowData['Material']) && $rowData['Material'] !== $currentMaterial) {
                    $currentMaterial = $rowData['Material'];
                    $groupedData[$currentMaterial] = [
                        'header' => [
                            'IV_MATERIAL' => $rowData['Material'] ?? '',
                            'IV_PLANT' => $rowData['Plant'] ?? '',
                            'IV_DESCRIPTION' => $rowData['Description'] ?? '',
                            'IV_TASK_LIST_USAGE' => $rowData['Usage'] ?? '1',
                            'IV_TASK_LIST_STATUS' => $rowData['Status'] ?? '4',
                            'IV_GROUP_COUNTER' => $rowData['Grp Ctr'] ?? '1',
                            'IV_TASK_MEASURE_UNIT' => $rowData['UoM'] ?? 'PC',
                        ],
                        'operations' => []
                    ];
                }
                if ($currentMaterial) {
                    $groupedData[$currentMaterial]['operations'][] = [
                        'ACTIVITY' => $rowData['Operation'] ?? '',
                        'WORK_CNTR' => $rowData['Work Cntr'] ?? '',
                        'CONTROL_KEY' => $rowData['Ctrl Key'] ?? '',
                        'DESCRIPTION' => $rowData['Descriptions'] ?? 'N/A',
                        'BASE_QTY' => $rowData['Base Qty'] ?? '1',
                        'UOM' => $rowData['UoM'] ?? 'PC',
                        'STD_VALUE_01' => $rowData['Activity 1'] ?? '0', 'STD_UNIT_01' => $rowData['UoM 1'] ?? '',
                        'STD_VALUE_02' => $rowData['Activity 2'] ?? '0', 'STD_UNIT_02' => $rowData['UoM 2'] ?? '',
                        'STD_VALUE_03' => $rowData['Activity 3'] ?? '0', 'STD_UNIT_03' => $rowData['UoM 3'] ?? '',
                        'STD_VALUE_04' => $rowData['Activity 4'] ?? '0', 'STD_UNIT_04' => $rowData['UoM 4'] ?? '',
                        'STD_VALUE_05' => $rowData['Activity 5'] ?? '0', 'STD_UNIT_05' => $rowData['UoM 5'] ?? '',
                        'STD_VALUE_06' => $rowData['Activity 6'] ?? '0', 'STD_UNIT_06' => $rowData['UoM 6'] ?? '',
                    ];
                }
            }
            return response()->json([
                'fileName' => $originalFileName,
                'data' => array_values($groupedData)
            ]);
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'Undefined array key') !== false) {
                return response()->json(['error' => 'Gagal memproses file. Pastikan nama kolom di file Excel sudah benar (contoh: Material, Plant, Description, Descriptions, dll).'], 500);
            }
            return response()->json(['error' => 'Gagal memproses file: ' . $e->getMessage()], 500);
        }
    }

    public function uploadToSap(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
            'routing_data' => 'required|array',
        ]);
        $pythonApiUrl = env('PYTHON_ROUTING_API_URL', 'http://127.0.0.1:5002') . '/upload_routing';
        try {
            $response = Http::timeout(300)->post($pythonApiUrl, [
                'username' => $request->username,
                'password' => $request->password,
                'routing_data' => $request->routing_data
            ]);
            return $response->json();
        } catch (\Exception $e) {
            return response()->json(['status' => 'Failed', 'message' => 'Tidak dapat terhubung ke service Python: ' . $e->getMessage()], 500);
        }
    }

    public function getWorkCenterDescription(Request $request)
    {
        $request->validate(['IV_WERKS' => 'required|string', 'IV_ARBPL' => 'required|string']);
        $pythonApiUrl = env('PYTHON_ROUTING_API_URL', 'http://127.0.0.1:5002') . '/get_work_center_desc';
        try {
            $response = Http::timeout(10)->post($pythonApiUrl, [
                'IV_WERKS' => $request->IV_WERKS,
                'IV_ARBPL' => $request->IV_ARBPL,
            ]);
            return response()->json($response->json(), $response->status());
        } catch (ConnectionException $e) {
            $errorMessage = "Gagal terhubung ke Python di URL: '{$pythonApiUrl}'. Pastikan service Python berjalan dan URL di file .env sudah benar. Detail: " . $e->getMessage();
            return response()->json(['error' => $errorMessage], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Terjadi error tak terduga: ' . $e->getMessage()], 500);
        }
    }
}
