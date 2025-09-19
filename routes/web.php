<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BomController;
use App\Http\Controllers\RoutingController; // Tambahkan ini

// Mengarahkan URL utama ('/') ke halaman Material Converter sebagai halaman default.
Route::get('/', function () {
    return redirect()->route('converter.index');
});

// ===================================================================
// == MATERIAL CONVERTER ROUTES ==
// ===================================================================
Route::get('/converter', [BomController::class, 'showMaterialConverter'])->name('converter.index');
Route::post('/converter/upload', [BomController::class, 'upload'])->name('converter.upload');
Route::get('/converter/download/{filename}', [BomController::class, 'download'])->name('converter.download');

// ===================================================================
// == BOM UPLOADER ROUTES ==
// ===================================================================
Route::get('/bom', [BomController::class, 'index'])->name('bom.index');
Route::post('/bom/upload', [BomController::class, 'processAndStoreFile'])->name('bom.upload');
Route::get('/bom/download/{filename}', [BomController::class, 'downloadProcessedFile'])->name('bom.download');

// ===================================================================
// == ROUTING ROUTES (BARU) ==
// ===================================================================
Route::get('/routing', [RoutingController::class, 'index'])->name('routing.index');


// ===================================================================
// == API ROUTES (UNTUK JAVASCRIPT) ==
// ===================================================================

// --- Material Converter API ---
Route::get('/api/material/generate', [BomController::class, 'generateNextMaterialCode'])->name('api.material.generate');
Route::post('/api/sap/stage', [BomController::class, 'stageMaterials'])->name('api.sap.stage');
Route::post('/api/sap/activate-and-upload', [BomController::class, 'activateAndUpload'])->name('api.sap.activate_and_upload');
Route::post('/api/sap/download-report', [BomController::class, 'downloadUploadReport'])->name('api.sap.download_report');

// --- BOM Uploader API ---
Route::post('/api/bom/generate-codes', [BomController::class, 'generateBomMaterialCodes'])->name('api.bom.generate_codes');
Route::post('/api/bom/upload-sap', [BomController::class, 'uploadProcessedBom'])->name('api.bom.upload');

// --- Notifikasi API ---
Route::post('/api/notification/send', [BomController::class, 'sendNotification'])->name('api.notification.send');

// Tambahkan di dalam file routes Anda
Route::post('/api/sap/create_inspection_plan', [App\Http\Controllers\BomController::class, 'createInspectionPlan'])->name('api.sap.create_inspection_plan');
