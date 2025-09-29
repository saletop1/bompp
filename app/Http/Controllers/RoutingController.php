<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Client\ConnectionException;
use App\Models\Routing; // <-- Kemungkinan besar baris ini yang hilang
use App\Models\DocumentSequence;
use Illuminate\Support\Facades\DB;

class RoutingController extends Controller
{
    public function index()
    {
        $pythonApiUrl = env('PYTHON_ROUTING_API_URL', 'http://127.0.0.1:5002');
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
                'data' => $groupData,
                'is_saved' => true,
                'document_number' => $docNumber
            ];
        }

        return view('routing', [
        'savedRoutings' => $formattedRoutings,
        'pythonApiUrl' => $pythonApiUrl
    ]);
    }

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
                   ->whereNull('uploaded_to_sap_at')
                   ->update(['uploaded_to_sap_at' => $now]);
        }

        return response()->json(['status' => 'success', 'message' => 'Data berhasil ditandai sebagai ter-upload.']);
    }

    public function saveRoutings(Request $request)
    {
        $request->validate([
            'routings' => 'required|array',
            'document_name' => 'required|string|max:255',
            'product_name' => 'required|string|max:255',
        ]);

        $routingsData = $request->input('routings');
        $docName = $request->input('document_name');
        $productName = $request->input('product_name');

        try {
            DB::transaction(function () use ($routingsData, $docName, $productName) {
                $materialsToSave = array_map(function ($routing) {
                    return $routing['header']['IV_MATERIAL'];
                }, $routingsData);

                if (!empty($materialsToSave)) {
                    Routing::whereIn('material', $materialsToSave)->delete();
                }

                $docNumber = $this->getNextDocumentNumber('RPP');
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
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Data berhasil disimpan/diperbarui.'
            ]);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Gagal menyimpan data: ' . $e->getMessage()], 500);
        }
    }

    private function getNextDocumentNumber(string $prefix): string
    {
        return DB::transaction(function () use ($prefix) {
            $sequence = DocumentSequence::where('prefix', $prefix)->lockForUpdate()->first();
            if ($sequence) {
                $sequence->last_sequence += 1;
                $sequence->save();
                return $prefix . '.' . str_pad($sequence->last_sequence, 9, '0', STR_PAD_LEFT);
            }

            $newSequence = DocumentSequence::create([
                'prefix' => $prefix,
                'last_sequence' => 1
            ]);
            return $prefix . '.' . str_pad($newSequence->last_sequence, 9, '0', STR_PAD_LEFT);
        });
    }

    public function deleteRoutings(Request $request)
    {
        $request->validate([
            'document_numbers' => 'required|array',
            'document_numbers.*' => 'string'
        ]);

        try {
            $docNumbers = $request->input('document_numbers');
            Routing::whereIn('document_number', $docNumbers)->delete();
            return response()->json([
                'status' => 'success',
                'message' => 'Dokumen yang dipilih berhasil dihapus dari database.'
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Gagal menghapus data dari database: ' . $e->getMessage()], 500);
        }
    }

    public function processFile(Request $request)
    {
        $request->validate(['routing_file' => 'required|mimes:xlsx,xls,csv']);
        try {
            $file = $request->file('routing_file');
            $originalFileName = $file->getClientOriginalName();
            $rows = Excel::toArray(new \stdClass(), $file)[0];

            if (count($rows) < 1) {
                throw new \Exception("File Excel kosong atau tidak memiliki baris header.");
            }
            $header = array_map('trim', array_shift($rows));
            $requiredHeaders = [
                'Material', 'Plant', 'Description', 'Usage', 'Status', 'Grp Ctr', 'UoM',
                'Operation', 'Work Cntr', 'Ctrl Key', 'Descriptions', 'Base Qty',
                'Activity 1', 'UoM 1', 'Activity 2', 'UoM 2', 'Activity 3', 'UoM 3',
                'Activity 4', 'UoM 4', 'Activity 5', 'UoM 5', 'Activity 6', 'UoM 6'
            ];

            $missingHeaders = array_diff($requiredHeaders, $header);
            if (!empty($missingHeaders)) {
                throw new \Exception("Template Excel tidak sesuai. Kolom berikut tidak ditemukan: " . implode(', ', $missingHeaders));
            }

            $groupedData = [];
            $currentMaterial = null;

            foreach ($rows as $rowIndex => $row) {
                if (empty(array_filter($row))) continue;
                $rowData = array_combine($header, $row);

                if (!empty($rowData['Material']) && $rowData['Material'] !== $currentMaterial) {
                    $currentMaterial = $rowData['Material'];
                    $groupedData[$currentMaterial] = [
                        'header' => [
                            'IV_MATERIAL' => $rowData['Material'] ?? '', 'IV_PLANT' => $rowData['Plant'] ?? '',
                            'IV_DESCRIPTION' => $rowData['Description'] ?? '', 'IV_TASK_LIST_USAGE' => $rowData['Usage'] ?? '1',
                            'IV_TASK_LIST_STATUS' => $rowData['Status'] ?? '4', 'IV_GROUP_COUNTER' => $rowData['Grp Ctr'] ?? '1',
                            'IV_TASK_MEASURE_UNIT' => $rowData['UoM'] ?? 'PC',
                        ],
                        'operations' => []
                    ];
                }
                if ($currentMaterial) {
                    $activityValues = [];
                    for ($i = 1; $i <= 6; $i++) {
                        $activityKey = 'Activity ' . $i; $uomKey = 'UoM ' . $i;
                        $value = $rowData[$activityKey] ?? '0';

                        if ($value !== null && $value !== '' && !is_numeric($value)) {
                            $material = $rowData['Material'] ?? $currentMaterial;
                            $operation = $rowData['Operation'] ?? 'N/A';
                            throw new \Exception(
                                "Data tidak valid di file Excel (baris " . ($rowIndex + 2) . "). \n" .
                                "Material: {$material}, Operasi: {$operation}. \n" .
                                "Kolom '{$activityKey}' harus berupa angka, tetapi ditemukan nilai: '{$value}'."
                            );
                        }
                        $activityValues['STD_VALUE_0' . $i] = ($value === null || $value === '') ? '0' : $value;
                        $activityValues['STD_UNIT_0' . $i] = $rowData[$uomKey] ?? '';
                    }

                    if (!empty($rowData['Operation'])) {
                        $groupedData[$currentMaterial]['operations'][] = array_merge([
                            'ACTIVITY' => $rowData['Operation'] ?? '', 'WORK_CNTR' => $rowData['Work Cntr'] ?? '',
                            'CONTROL_KEY' => $rowData['Ctrl Key'] ?? '', 'DESCRIPTION' => $rowData['Descriptions'] ?? 'N/A',
                            'BASE_QTY' => $rowData['Base Qty'] ?? '1', 'UOM' => $rowData['UoM'] ?? 'PC',
                        ], $activityValues);
                    }
                }
            }
            return response()->json([
                'fileName' => $originalFileName,
                'data' => array_values($groupedData),
                'is_saved' => false
            ]);
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'Undefined array key') !== false) {
                return response()->json(['error' => 'Gagal memproses file. Pastikan nama kolom di file Excel sudah benar.'], 500);
            }
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function uploadToSap(Request $request)
    {
        $request->validate([
            'username' => 'required|string', 'password' => 'required|string', 'routing_data' => 'required|array',
        ]);

        $submittedUsername = $request->input('username');
        $allowedUsersEnv = env('SAP_ROUTING_RELEASE_USERS', '');
        $allowedUsers = array_map('strtoupper', array_map('trim', explode(',', $allowedUsersEnv)));

        if (!in_array(strtoupper($submittedUsername), $allowedUsers)) {
            return response()->json([
                'status' => 'Failed',
                'message' => 'Otorisasi Gagal. Username Anda (' . $submittedUsername . ') tidak memiliki izin untuk melakukan Release.'
            ], 403);
        }

        $pythonApiUrl = env('PYTHON_ROUTING_API_URL', 'http://127.0.0.1:5002') . '/upload_routing';
        try {
            $response = Http::timeout(300)->post($pythonApiUrl, [
                'username' => $request->username, 'password' => $request->password,
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
                'IV_WERKS' => $request->IV_WERKS, 'IV_ARBPL' => $request->IV_ARBPL,
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
        $existingRouting = Routing::whereIn('material', $materials)->first();

        if ($existingRouting) {
            return response()->json([
                'exists' => true, 'document_name' => $existingRouting->document_name,
                'document_number' => $existingRouting->document_number,
                'material' => $existingRouting->material
            ]);
        }
        return response()->json(['exists' => false]);
    }
}
