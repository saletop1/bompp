<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BomController;


// Mengarahkan URL utama ('/') ke halaman Material Converter sebagai halaman default.
Route::get('/', function () {
    return redirect()->route('converter.index');
});

// ===================================================================
// == MATERIAL CONVERTER ROUTES ==
// ===================================================================

// Menampilkan halaman form Material Converter
Route::get('/converter', [BomController::class, 'showMaterialConverter'])->name('converter.index');

// Memproses file yang diunggah dari form Material Converter
Route::post('/converter/upload', [BomController::class, 'upload'])->name('converter.upload');

// Mengunduh file material yang telah diproses
Route::get('/converter/download/{filename}', [BomController::class, 'download'])->name('converter.download');


// ===================================================================
// == BOM UPLOADER ROUTES ==
// ===================================================================

// Menampilkan halaman form BOM Uploader
Route::get('/bom', [BomController::class, 'index'])->name('bom.index');

// Memproses file yang diunggah dari form BOM Uploader
Route::post('/bom/upload', [BomController::class, 'processAndStoreFile'])->name('bom.upload');

// Mengunduh file BOM yang telah diproses
Route::get('/bom/download/{filename}', [BomController::class, 'downloadProcessedFile'])->name('bom.download');


// ===================================================================
// == API ROUTES (UNTUK JAVASCRIPT) ==
// ===================================================================

// API untuk mendapatkan kode material berikutnya (digunakan di Material Converter)
Route::get('/api/material/generate', [BomController::class, 'generateNextMaterialCode'])->name('api.material.generate');

// API untuk mengunggah material master yang sudah diproses ke SAP
Route::post('/api/material/upload-sap', [BomController::class, 'uploadToSap'])->name('api.sap.upload');

// API untuk mengaktivasi Quality Management (QM)
Route::post('/api/material/activate-qm', [BomController::class, 'activateQm'])->name('api.qm.activate');

// API untuk mencari dan mengisi kode material BOM yang kosong (tombol "Generate")
Route::post('/api/bom/generate-codes', [BomController::class, 'generateBomMaterialCodes'])->name('api.bom.generate_codes');

// API untuk mengunggah BOM yang sudah diproses ke SAP
Route::post('/api/bom/upload-sap', [BomController::class, 'uploadProcessedBom'])->name('api.bom.upload');

// API untuk mengirim notifikasi email (jika diimplementasikan)
Route::post('/api/notification/send', [BomController::class, 'sendNotification'])->name('api.notification.send');

