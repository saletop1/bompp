<?php

// use App\Http\Controllers\AuthController; // Menggunakan LoginController di bawah
use App\Http\Controllers\Auth\LoginController; // Menggunakan file standar
use App\Http\Controllers\BomController;
use App\Http\Controllers\RoutingController;
// use App\Http\Controllers\ConverterController; // Digabung ke BomController
use Illuminate\Support\Facades\Route;

// Rute publik
Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.post');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Rute yang membutuhkan autentikasi
Route::middleware(['auth'])->group(function () {

    // Converter routes (menggunakan BomController)
    Route::get('/converter', [BomController::class, 'showMaterialConverter'])->name('converter.index');
    Route::post('/converter/upload', [BomController::class, 'upload'])->name('converter.upload');
    Route::get('/converter/download/{filename}', [BomController::class, 'download'])->name('converter.download');

    // BOM routes (menggunakan BomController)
    Route::get('/bom', [BomController::class, 'index'])->name('bom.index');
    Route::post('/bom/upload', [BomController::class, 'processAndStoreFile'])->name('bom.upload');
    Route::get('/bom/download/{filename}', [BomController::class, 'downloadProcessedFile'])->name('bom.download');
    Route::get('/bom/download-routing/{filename}', [BomController::class, 'downloadRoutingTemplate'])->name('bom.download_routing_template');

    // Routing routes
    Route::get('/routing', [RoutingController::class, 'index'])->name('routing.index');

    // API routes
    Route::prefix('api')->group(function () {

        // Converter API (menggunakan BomController)
        Route::post('/material/generate', [BomController::class, 'generateNextMaterialCode'])->name('api.material.generate');
        Route::post('/sap/stage', [BomController::class, 'stageMaterials'])->name('api.sap.stage');
        Route::post('/sap/activate_and_upload', [BomController::class, 'activateAndUpload'])->name('api.sap.activate_and_upload');
        Route::post('/sap/create_inspection_plan', [BomController::class, 'createInspectionPlan'])->name('api.sap.create_inspection_plan');
        Route::post('/notification/send', [BomController::class, 'sendNotification'])->name('api.notification.send');

        // BOM API (menggunakan BomController)
        Route::post('/bom/generate_codes', [BomController::class, 'generateBomMaterialCodes'])->name('api.bom.generate_codes');
        Route::post('/bom/api_find_material', [BomController::class, 'apiFindMaterialCode'])->name('api.bom.api_find_material');
        Route::post('/bom/save_generated_codes', [BomController::class, 'saveGeneratedCodes'])->name('api.bom.save_generated_codes');
        Route::post('/bom/upload', [BomController::class, 'uploadProcessedBom'])->name('api.bom.upload');
        Route::post('/bom/upload_single', [BomController::class, 'uploadSingleBom'])->name('api.bom.upload_single');
        Route::post('/notification/sendBom', [BomController::class, 'sendBomNotification'])->name('api.notification.sendBom');

        // [PERBAIKAN] Routing API - Menyesuaikan NAMA rute agar cocok dengan panggilan di index.blade.php
        Route::post('/routing/process-file', [RoutingController::class, 'processFile'])->name('routing.processFile');
        Route::post('/routing/save', [RoutingController::class, 'saveRoutings'])->name('routing.save');
        // 'uploadToSap' adalah satu-satunya yang dipanggil dengan 'api.' di JS Anda, jadi biarkan.
        Route::post('/routing/upload-sap', [RoutingController::class, 'uploadToSap'])->name('api.routing.uploadToSap');
        Route::post('/routing/mark-uploaded', [RoutingController::class, 'markAsUploaded'])->name('routing.markAsUploaded');
        // 'updateStatus' sudah benar
        Route::post('/routing/update-status', [RoutingController::class, 'updateStatus'])->name('routing.updateStatus');
        Route::post('/routing/delete', [RoutingController::class, 'deleteRoutings'])->name('routing.delete');
        Route::post('/routing/delete-rows', [RoutingController::class, 'deleteRoutingRows'])->name('routing.deleteRows');
        Route::post('/routing/check-doc-name', [RoutingController::class, 'checkDocumentNameExists'])->name('routing.checkDocName');
        Route::post('/routing/check-materials', [RoutingController::class, 'checkMaterialsInExistingDocument'])->name('routing.checkMaterials');
        
    });

});
