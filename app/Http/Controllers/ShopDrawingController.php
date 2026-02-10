<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ShopDrawing;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use App\Mail\DrawingRequestEmail;

class ShopDrawingController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->input('query', '');
        $plant = $request->input('plant', '');
        
        $drawings = ShopDrawing::with('user')
            ->when($query, function ($q) use ($query) {
                $q->where('material_code', 'like', "%{$query}%");
            })
            ->when($plant, function ($q) use ($plant) {
                $q->where('plant', $plant);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(12);
        
        $groupedDrawings = [];
        foreach ($drawings as $drawing) {
            $materialCode = $drawing->material_code;
            if (!isset($groupedDrawings[$materialCode])) {
                $groupedDrawings[$materialCode] = [
                    'material_code' => $materialCode,
                    'description' => $drawing->description,
                    'material_type' => $drawing->material_type ?? 'N/A',
                    'material_group' => $drawing->material_group ?? 'N/A',
                    'base_unit' => $drawing->base_unit ?? 'N/A',
                    'drawings' => [],
                    'last_uploader' => null,
                    'last_upload_date' => null,
                    'uploaded_by' => $drawing->user->name ?? 'N/A',
                    'uploaded_at' => $drawing->created_at,
                ];
            }
            
            $groupedDrawings[$materialCode]['drawings'][] = $drawing;
            
            if (!$groupedDrawings[$materialCode]['last_upload_date'] || 
                $drawing->created_at > $groupedDrawings[$materialCode]['last_upload_date']) {
                $groupedDrawings[$materialCode]['last_uploader'] = $drawing->user->name ?? 'N/A';
                $groupedDrawings[$materialCode]['last_upload_date'] = $drawing->created_at;
            }
        }
        
        // Check if user can upload drawings
        $canUpload = auth()->user()->canUploadDrawings();
        
        return view('shop_drawings', compact('drawings', 'query', 'plant', 'groupedDrawings', 'canUpload'));
    }
    
    public function validateMaterial(Request $request)
    {
        try {
            $request->validate([
                'material_code' => 'required|string',
                'plant' => 'required|string'
            ]);
            
            $pythonServiceUrl = env('PYTHON_DROPBOX_API_URL', 'http://localhost:5003');
            
            Log::info('Calling Python service for material validation', [
                'url' => $pythonServiceUrl . '/validate_material',
                'material_code' => $request->input('material_code'),
                'plant' => $request->input('plant')
            ]);
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('API_TOKEN', 'your-secret-api-token-12345'),
                'Content-Type' => 'application/json',
            ])->post($pythonServiceUrl . '/validate_material', [
                'material_code' => $request->input('material_code'),
                'plant' => $request->input('plant', '')
            ]);
            
            Log::info('Python service validation response', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            
            if ($response->successful()) {
                $responseData = $response->json();
                Log::info('Validation successful', ['data' => $responseData]);
                return response()->json($responseData);
            } else {
                Log::error('Python service validation failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                
                return response()->json([
                    'status' => 'error',
                    'is_valid' => false,
                    'message' => 'Material validation failed: ' . $response->body()
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('Material validation error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'is_valid' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function searchMaterial(Request $request)
    {
        try {
            $request->validate([
                'material_code' => 'required|string'
            ]);
            
            $pythonServiceUrl = env('PYTHON_DROPBOX_API_URL', 'http://localhost:5003');
            $materialCode = $request->input('material_code', '');
            
            Log::info('Calling Python service for material search', [
                'url' => $pythonServiceUrl . '/search_material',
                'material_code' => $materialCode
            ]);
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('API_TOKEN', 'your-secret-api-token-12345'),
                'Content-Type' => 'application/json',
            ])->post($pythonServiceUrl . '/search_material', [
                'material_code' => $materialCode
            ]);
            
            Log::info('Python service search response', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            
            if ($response->successful()) {
                $responseData = $response->json();
                Log::info('Search successful', [
                    'status' => $responseData['status'] ?? 'unknown',
                    'count' => $responseData['count'] ?? 0,
                    'materials' => isset($responseData['materials']) ? count($responseData['materials']) : 0
                ]);
                return response()->json($responseData);
            } else {
                Log::error('Python service search failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to fetch data from Python service: ' . $response->body()
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Material search error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function getMaterialInfo(Request $request)
    {
        try {
            $request->validate([
                'material_code' => 'required|string'
            ]);
            
            $pythonServiceUrl = env('PYTHON_DROPBOX_API_URL', 'http://localhost:5003');
            $materialCode = $request->input('material_code', '');
            
            Log::info('Calling Python service for material info', [
                'url' => $pythonServiceUrl . '/get_material_info',
                'material_code' => $materialCode
            ]);
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('API_TOKEN', 'your-secret-api-token-12345'),
                'Content-Type' => 'application/json',
            ])->post($pythonServiceUrl . '/get_material_info', [
                'material_code' => $materialCode
            ]);
            
            Log::info('Python service material info response', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            
            if ($response->successful()) {
                $responseData = $response->json();
                Log::info('Material info successful', [
                    'status' => $responseData['status'] ?? 'unknown',
                    'source' => $responseData['source'] ?? 'unknown'
                ]);
                return response()->json($responseData);
            } else {
                Log::error('Python service material info failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to fetch material info from Python service: ' . $response->body()
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Material info error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function uploadDrawing(Request $request)
    {
        // Check if user is authorized to upload
        if (!auth()->user()->canUploadDrawings()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized: Only RnD users can upload drawings'
            ], 403);
        }
        
        try {
            $request->validate([
                'material_code' => 'required|string',
                'plant' => 'required|string',
                'description' => 'required|string',
                'drawing' => 'required|mimes:jpg,jpeg,png,gif,bmp,pdf,dwg,dxf,igs,iges,stp,step,zip,rar',
                'drawing_type' => 'required|string|in:assembly,detail,exploded,orthographic,perspective,fabrication',
                'version' => 'required|string'
            ]);
            
            $pythonServiceUrl = env('PYTHON_DROPBOX_API_URL', 'http://localhost:5003');
            
            $validateResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('API_TOKEN', 'your-secret-api-token-12345'),
                'Content-Type' => 'application/json',
            ])->post($pythonServiceUrl . '/validate_material', [
                'material_code' => $request->input('material_code'),
                'plant' => $request->input('plant')
            ]);
            
            if (!$validateResponse->successful()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Material validation failed. Please check material code and plant.'
                ], 400);
            }
            
            $validationData = $validateResponse->json();
            
            $materialInfo = $validationData['material'] ?? [];
            $materialType = $materialInfo['material_type'] ?? 'N/A';
            $materialGroup = $materialInfo['material_group'] ?? 'N/A';
            $baseUnit = $materialInfo['base_unit'] ?? 'N/A';
            
            if ($baseUnit === 'ST') {
                $baseUnit = 'PC';
            }
            
            if (!$validationData['is_valid'] ?? false) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Material validation failed. Please check material code and plant.'
                ], 400);
            }
            
            $version = $this->standardizeVersion($request->input('version', 'Ver.0'));
            
            $duplicateCheck = ShopDrawing::where('material_code', $request->input('material_code'))
            ->where('plant', $request->input('plant'))
            ->where('drawing_type', $request->input('drawing_type', 'assembly'))
            ->where('version', $version)
            ->where('original_filename', $request->file('drawing')->getClientOriginalName())
            ->exists();
        
            if ($duplicateCheck) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'A drawing with the same material code, drawing type, version, and filename already exists.'
                ], 400);
            }
            
            $uploadResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('API_TOKEN', 'your-secret-api-token-12345'),
            ])->attach(
                'drawing', 
                file_get_contents($request->file('drawing')->getRealPath()),
                $request->file('drawing')->getClientOriginalName()
            )->post($pythonServiceUrl . '/upload_shop_drawing', [
                'material_code' => $request->input('material_code'),
                'plant' => $request->input('plant'),
                'description' => $request->input('description'),
                'drawing_type' => $request->input('drawing_type', 'assembly'),
                'version' => $version,
                'username' => auth()->user()->name,
                'user_id' => auth()->id()
            ]);
            
            if ($uploadResponse->successful()) {
                $result = $uploadResponse->json();
                
                $shopDrawing = ShopDrawing::create([
                    'material_code' => $request->input('material_code'),
                    'plant' => $request->input('plant'),
                    'description' => $request->input('description'),
                    'drawing_type' => $request->input('drawing_type', 'assembly'),
                    'version' => $version,
                    'dropbox_file_id' => $result['file_id'] ?? null,
                    'dropbox_path' => $result['path'] ?? null,
                    'dropbox_share_url' => $result['share_url'] ?? null,
                    'dropbox_direct_url' => $result['direct_url'] ?? null,
                    'filename' => $result['filename'] ?? null,
                    'original_filename' => $result['original_filename'] ?? null,
                    'file_size' => $result['size'] ?? 0,
                    'file_extension' => $request->file('drawing')->getClientOriginalExtension(),
                    'user_id' => auth()->id(),
                    'uploaded_at' => now(),
                    'material_type' => $materialType,
                    'material_group' => $materialGroup,
                    'base_unit' => $baseUnit
                ]);
                
                Log::info('Shop drawing uploaded with material info:', [
                    'material_code' => $request->input('material_code'),
                    'material_type' => $materialType,
                    'material_group' => $materialGroup,
                    'base_unit' => $baseUnit,
                    'sap_response' => $validationData
                ]);
                
                return response()->json([
                    'status' => 'success',
                    'message' => 'Shop drawing uploaded successfully',
                    'drawing' => $shopDrawing
                ]);
                
            } else {
                Log::error('Dropbox upload failed:', ['response' => $uploadResponse->body()]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Dropbox upload failed: ' . $uploadResponse->body()
                ], 500);
            }
            
        } catch (\Exception $e) {
            Log::error('Drawing upload error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Upload failed: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function uploadMultipleDrawings(Request $request)
    {
        // Check if user is authorized to upload
        if (!auth()->user()->canUploadDrawings()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized: Only RnD users can upload drawings'
            ], 403);
        }
        
        try {
            $request->validate([
                'material_code' => 'required|string',
                'plant' => 'required|string',
                'description' => 'required|string',
                'file_count' => 'required|integer|min:1',
            ]);
            
            Log::info('=== START UPLOAD MULTIPLE DRAWINGS ===');
            Log::info('Request data:', $request->all());
            
            $pythonServiceUrl = env('PYTHON_DROPBOX_API_URL', 'http://localhost:5003');
            
            Log::info('Validating material with SAP...', [
                'material_code' => $request->input('material_code'),
                'plant' => $request->input('plant'),
                'python_url' => $pythonServiceUrl
            ]);
            
            $validateResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('API_TOKEN', 'your-secret-api-token-12345'),
                'Content-Type' => 'application/json',
            ])->post($pythonServiceUrl . '/validate_material', [
                'material_code' => $request->input('material_code'),
                'plant' => $request->input('plant')
            ]);
            
            Log::info('SAP Validation Response Status:', ['status' => $validateResponse->status()]);
            Log::info('SAP Validation Response Body:', ['body' => $validateResponse->body()]);
            
            if (!$validateResponse->successful()) {
                Log::error('SAP validation failed:', [
                    'status' => $validateResponse->status(),
                    'body' => $validateResponse->body()
                ]);
                
                return response()->json([
                    'status' => 'error',
                    'message' => 'Material validation failed. Please check material code and plant.'
                ], 400);
            }
            
            $validationData = $validateResponse->json();
            Log::info('SAP Validation Data:', $validationData);
            
            if (!isset($validationData['is_valid']) || !$validationData['is_valid']) {
                Log::error('Material is not valid in SAP:', $validationData);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Material is not valid in SAP: ' . ($validationData['message'] ?? 'Unknown error')
                ], 400);
            }
            
            if (!isset($validationData['material'])) {
                Log::warning('No material data in SAP response, using defaults');
                $materialInfo = [];
            } else {
                $materialInfo = $validationData['material'];
            }
            
            $materialType = $materialInfo['material_type'] ?? 'N/A';
            $materialGroup = $materialInfo['material_group'] ?? 'N/A';
            $baseUnit = $materialInfo['base_unit'] ?? 'N/A';
            
            if ($baseUnit === 'ST') {
                $baseUnit = 'PC';
            }
            
            Log::info('Material info to save:', [
                'material_type' => $materialType,
                'material_group' => $materialGroup,
                'base_unit' => $baseUnit
            ]);
            
            $uploadedCount = 0;
            $failedCount = 0;
            $errors = [];
            
            $processedCombinations = [];
            
            for ($i = 0; $i < $request->input('file_count'); $i++) {
                if (!$request->hasFile("files.$i.file")) {
                    continue;
                }
                
                $file = $request->file("files.$i.file");
                $drawingType = $request->input("files.$i.drawing_type", 'assembly');
                $originalVersion = $request->input("files.$i.version", 'Ver.0');
                $version = $this->standardizeVersion($originalVersion);
                
                $combinationKey = $drawingType . '_' . $version;
                if (in_array($combinationKey, $processedCombinations)) {
                    $failedCount++;
                    $errors[] = "File " . ($i + 1) . ": Drawing type '{$drawingType}' with version '{$version}' already exists in this batch. Each drawing type must have a unique version.";
                    continue;
                }
                
                $validator = \Validator::make(
                    [
                        'drawing_type' => $drawingType,
                        'version' => $originalVersion,
                        'file' => $file
                    ],
                    [
                        'drawing_type' => 'required|string|in:assembly,detail,exploded,orthographic,perspective,fabrication',
                        'version' => 'required|string',
                        'file' => 'required|max:102400'
                    ]
                );
                
                $validator->after(function ($validator) use ($file, $i) {
                    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'pdf', 'dwg', 'dxf', 'igs', 'iges', 'stp', 'step', 'zip', 'rar'];
                    $extension = strtolower($file->getClientOriginalExtension());
                    
                    if (!in_array($extension, $allowedExtensions)) {
                        $validator->errors()->add(
                            'file', 
                            "File " . ($i + 1) . " must be a file of type: " . implode(', ', $allowedExtensions)
                        );
                    }
                });
                
                if ($validator->fails()) {
                    $failedCount++;
                    $errorMessages = $validator->errors()->all();
                    $errors[] = "File " . ($i + 1) . ": " . implode(', ', $errorMessages);
                    Log::error('File validation failed:', [
                        'file' => $file->getClientOriginalName(),
                        'errors' => $errorMessages
                    ]);
                    continue;
                }
                
                $duplicateCheck = ShopDrawing::where('material_code', $request->input('material_code'))
                    ->where('plant', $request->input('plant'))
                    ->where('drawing_type', $drawingType)
                    ->where('version', $version)
                    ->exists();
                
                if ($duplicateCheck) {
                    $failedCount++;
                    $errors[] = "File " . ($i + 1) . ": A drawing with material code '{$request->input('material_code')}', drawing type '{$drawingType}', and version '{$version}' already exists in the system.";
                    continue;
                }
                
                try {
                    Log::info('Uploading file to Dropbox:', [
                        'file_index' => $i,
                        'filename' => $file->getClientOriginalName(),
                        'drawing_type' => $drawingType,
                        'version' => $version
                    ]);
                    
                    $uploadResponse = Http::withHeaders([
                        'Authorization' => 'Bearer ' . env('API_TOKEN', 'your-secret-api-token-12345'),
                    ])->attach(
                        'drawing', 
                        file_get_contents($file->getRealPath()),
                        $file->getClientOriginalName()
                    )->post($pythonServiceUrl . '/upload_shop_drawing', [
                        'material_code' => $request->input('material_code'),
                        'plant' => $request->input('plant'),
                        'description' => $request->input('description'),
                        'drawing_type' => $drawingType,
                        'version' => $version,
                        'username' => auth()->user()->name,
                        'user_id' => auth()->id()
                    ]);
                    
                    Log::info('Dropbox Upload Response Status:', ['status' => $uploadResponse->status()]);
                    Log::info('Dropbox Upload Response Body:', ['body' => $uploadResponse->body()]);
                    
                    if ($uploadResponse->successful()) {
                        $result = $uploadResponse->json();
                        
                        $drawingData = [
                            'material_code' => $request->input('material_code'),
                            'plant' => $request->input('plant'),
                            'description' => $request->input('description'),
                            'drawing_type' => $drawingType,
                            'version' => $version,
                            'dropbox_file_id' => $result['file_id'] ?? null,
                            'dropbox_path' => $result['path'] ?? null,
                            'dropbox_share_url' => $result['share_url'] ?? null,
                            'dropbox_direct_url' => $result['direct_url'] ?? null,
                            'filename' => $result['filename'] ?? null,
                            'original_filename' => $result['original_filename'] ?? null,
                            'file_size' => $result['size'] ?? 0,
                            'file_extension' => $file->getClientOriginalExtension(),
                            'user_id' => auth()->id(),
                            'uploaded_at' => now(),
                            'material_type' => $materialType,
                            'material_group' => $materialGroup,
                            'base_unit' => $baseUnit
                        ];
                        
                        Log::info('Saving drawing to database:', $drawingData);
                        
                        $shopDrawing = ShopDrawing::create($drawingData);
                        
                        Log::info('Drawing saved with ID:', ['id' => $shopDrawing->id]);
                        
                        $processedCombinations[] = $combinationKey;
                        $uploadedCount++;
                        
                    } else {
                        $failedCount++;
                        $errors[] = "File " . ($i + 1) . ": Dropbox upload failed";
                        Log::error('Dropbox upload failed for file ' . ($i + 1), [
                            'response' => $uploadResponse->body()
                        ]);
                    }
                } catch (\Exception $e) {
                    $failedCount++;
                    $errors[] = "File " . ($i + 1) . ": " . $e->getMessage();
                    Log::error('Error uploading file ' . ($i + 1) . ': ' . $e->getMessage());
                }
            }
            
            Log::info('Upload completed:', [
                'uploaded_count' => $uploadedCount,
                'failed_count' => $failedCount,
                'errors' => $errors
            ]);
            
            if ($uploadedCount > 0) {
                return response()->json([
                    'status' => 'success',
                    'message' => "Successfully uploaded {$uploadedCount} drawing(s). " . 
                                ($failedCount > 0 ? "Failed to upload {$failedCount} file(s)." : ""),
                    'uploaded_count' => $uploadedCount,
                    'failed_count' => $failedCount,
                    'errors' => $errors
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to upload any files. ' . implode('; ', $errors),
                    'errors' => $errors
                ], 400);
            }
            
        } catch (\Exception $e) {
            Log::error('Multiple drawings upload error: ' . $e->getMessage());
            Log::error('Stack trace:', ['trace' => $e->getTraceAsString()]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Upload failed: ' . $e->getMessage()
            ], 500);
        }
    }
    
    private function standardizeVersion($version)
    {
        if (empty($version)) {
            return 'Ver.0';
        }
        
        $original = trim($version);
        
        if (strtolower($original) === 'master') {
            return 'Ver.0';
        }
        
        $cleaned = preg_replace('/[\s\-_]/', '', $original);
        
        if (preg_match('/^ver\.?(\d+)$/i', $cleaned, $matches)) {
            $number = (int)$matches[1];
            return 'Ver.' . $number;
        }
        
        if (is_numeric($cleaned)) {
            $number = (int)$cleaned;
            return 'Ver.' . $number;
        }
        
        if (preg_match('/\d+/', $cleaned, $matches)) {
            $number = (int)$matches[0];
            return 'Ver.' . $number;
        }
        
        return 'Ver.0';
    }
    
    private function sendEmailNotification($shopDrawing)
    {
        // Implement email notification logic here
    }
    
    private function sendBulkEmailNotification($materialCode, $count, $username, $materialType)
    {
        // Implement bulk email notification logic here
    }
    
    public function deleteDrawing($id)
    {
        try {
            $drawing = ShopDrawing::findOrFail($id);
            
            // Check permissions - hanya user yang mengupload atau user dengan role RnD yang bisa delete
            if ($drawing->user_id !== auth()->id() && !auth()->user()->isRnD()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are not authorized to delete this drawing'
                ], 403);
            }
            
            $pythonServiceUrl = env('PYTHON_DROPBOX_API_URL', 'http://localhost:5003');
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('API_TOKEN', 'your-secret-api-token-12345'),
                'Content-Type' => 'application/json',
            ])->post($pythonServiceUrl . '/delete_shop_drawing', [
                'file_id' => $drawing->dropbox_file_id,
                'path' => $drawing->dropbox_path
            ]);
            
            if ($response->successful()) {
                $drawing->delete();
                
                return response()->json([
                    'status' => 'success',
                    'message' => 'Shop drawing deleted successfully'
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to delete from Dropbox'
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('Delete drawing error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function getShopDrawings(Request $request)
    {
        try {
            $materialCode = $request->input('material_code');
            $plant = $request->input('plant');
            
            $drawings = ShopDrawing::with('user')
                ->where('material_code', $materialCode)
                ->when($plant, function ($q) use ($plant) {
                    $q->where('plant', $plant);
                })
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($drawing) {
                    $drawingArray = $drawing->toArray();
                    $drawingArray['user_name'] = $drawing->user ? $drawing->user->name : 'N/A';
                    return $drawingArray;
                });
            
            return response()->json([
                'status' => 'success',
                'drawings' => $drawings,
                'count' => $drawings->count()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Get shop drawings error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function previewDrawing($id)
    {
        try {
            $drawing = ShopDrawing::findOrFail($id);
            
            return response()->json([
                'status' => 'success',
                'drawing' => $drawing,
                'preview_url' => $drawing->dropbox_direct_url . '?raw=1'
            ]);
        } catch (\Exception $e) {
            Log::error('Preview drawing error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to preview drawing'
            ], 500);
        }
    }
    
    public function sendEmailRequest(Request $request)
    {
        try {
            $request->validate([
                'material_code' => 'required|string',
                'recipient_email' => 'required|email',
                'subject' => 'required|string',
                'message' => 'required|string',
            ]);
            
            $materialCode = $request->input('material_code');
            $recipientEmail = $request->input('recipient_email');
            $subject = $request->input('subject');
            $messageText = $request->input('message');
            $user = auth()->user();
            
            Log::info('Sending drawing request email', [
                'material_code' => $materialCode,
                'recipient_email' => $recipientEmail,
                'subject' => $subject,
                'user' => $user->name,
                'user_email' => $user->email
            ]);
            
            Mail::raw($messageText, function ($mail) use ($recipientEmail, $subject, $user) {
                $mail->to($recipientEmail)
                    ->subject($subject)
                    ->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'))
                    ->replyTo($user->email, $user->name);
            });
            
            Log::info('Email sent successfully');
            
            return response()->json([
                'status' => 'success',
                'message' => 'Email request sent successfully'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Send email error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to send email: ' . $e->getMessage()
            ], 500);
        }
    }
}