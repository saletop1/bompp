<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BomController;
use App\Http\Controllers\RoutingController;


// Mengarahkan URL utama ('/') ke halaman Material Converter sebagai halaman default.
Route::get('/', function () {
    return redirect()->route('converter.index');
});

// ===================================================================
// == HALAMAN UTAMA UNTUK SETIAP FITUR ==
// ===================================================================
Route::get('/converter', [BomController::class, 'showMaterialConverter'])->name('converter.index');
Route::get('/bom', [BomController::class, 'index'])->name('bom.index');
Route::get('/routing', [RoutingController::class, 'index'])->name('routing.index');


// ===================================================================
// == FUNGSI UPLOAD & DOWNLOAD STANDAR ==
// ===================================================================
// --- Material Converter ---
Route::post('/converter/upload', [BomController::class, 'upload'])->name('converter.upload');
Route::get('/converter/download/{filename}', [BomController::class, 'download'])->name('converter.download');

// --- BOM Uploader ---
Route::post('/bom/upload', [BomController::class, 'processAndStoreFile'])->name('bom.upload');
Route::get('/bom/download/{filename}', [BomController::class, 'downloadProcessedFile'])->name('bom.download');
// Rute untuk men-download template routing yang sudah diisi
Route::get('/bom/download-routing/{filename}', [BomController::class, 'downloadRoutingTemplate'])->name('bom.download_routing_template');


// ===================================================================
// == API ROUTES (UNTUK JAVASCRIPT DARI SEMUA HALAMAN) ==
// ===================================================================

// --- Material Converter API ---
Route::get('/api/material/generate', [BomController::class, 'generateNextMaterialCode'])->name('api.material.generate');
Route::post('/api/sap/stage', [BomController::class, 'stageMaterials'])->name('api.sap.stage');
Route::post('/api/sap/activate-and-upload', [BomController::class, 'activateAndUpload'])->name('api.sap.activate_and_upload');
Route::post('/api/sap/download-report', [BomController::class, 'downloadUploadReport'])->name('api.sap.download_report');
Route::post('/api/sap/create_inspection_plan', [BomController::class, 'createInspectionPlan'])->name('api.sap.create_inspection_plan');

// --- BOM Uploader API ---
// [DIPERBARUI] Rute ini sekarang mengambil DAFTAR DESKRIPSI
Route::post('/api/bom/generate-codes', [BomController::class, 'generateBomMaterialCodes'])->name('api.bom.generate_codes');
// [BARU] Rute untuk mencari SATU kode
Route::post('/api/bom/find-single-code', [BomController::class, 'apiFindMaterialCode'])->name('api.bom.find_single_code');
// [BARU] Rute untuk menyimpan SEMUA kode yang ditemukan
Route::post('/api/bom/save-generated-codes', [BomController::class, 'saveGeneratedCodes'])->name('api.bom.save_codes');

// [DIPERBARUI] Rute ini sekarang mengambil DAFTAR BOM
Route::post('/api/bom/upload-sap', [BomController::class, 'uploadProcessedBom'])->name('api.bom.upload');
// Rute ini untuk meng-upload SATU BOM per panggilan
Route::post('/api/bom/upload-single', [BomController::class, 'uploadSingleBom'])->name('api.bom.upload_single');


// --- Routing API ---
Route::post('/routing/process-file', [RoutingController::class, 'processFile'])->name('routing.processFile');
Route::post('/routing/upload-to-sap', [RoutingController::class, 'uploadToSap'])->name('api.routing.uploadToSap');
// Route::post('/routing/get-workcenter-desc', [RoutingController::class, 'getWorkCenterDescription'])->name('routing.getWorkCenterDesc');
Route::post('/routing/save', [RoutingController::class, 'saveRoutings'])->name('routing.save');
Route::post('/routing/mark-as-uploaded', [RoutingController::class, 'markAsUploaded'])->name('routing.markAsUploaded');
Route::post('/routing/delete', [RoutingController::class, 'deleteRoutings'])->name('routing.delete');
Route::post('/routing/check-name', [RoutingController::class, 'checkDocumentNameExists'])->name('routing.checkName');
Route::post('/routing/check-materials', [RoutingController::class, 'checkMaterialsInExistingDocument'])->name('routing.checkMaterials');
Route::post('/routing/delete-rows', [RoutingController::class, 'deleteRoutingRows'])->name('routing.deleteRows');
Route::post('/routing/update-status', [RoutingController::class, 'updateStatus'])->name('routing.updateStatus');

// --- Notifikasi API ---
Route::post('/api/notification/send', [BomController::class, 'sendNotification'])->name('api.notification.send');
Route::post('/api/notification/send-bom', [BomController::class, 'sendBomNotification'])->name('api.notification.sendBom');

