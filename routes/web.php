<?php

use App\Http\Controllers\ExcelConverterController;

// Rute untuk menampilkan halaman utama
Route::get('/', [ExcelConverterController::class, 'index'])->name('converter.index');

// Rute untuk memproses file upload dari form
Route::post('/upload', [ExcelConverterController::class, 'upload'])->name('converter.upload');

// Rute untuk men-download file yang sudah diproses
Route::get('/download/{filename}', [ExcelConverterController::class, 'download'])->name('converter.download');

// -- RUTE API UNTUK TOMBOL UPLOAD TO SAP --
// Gunakan prefix /api dan nama yang jelas
Route::post('/api/upload-to-sap', [ExcelConverterController::class, 'uploadToSap'])->name('api.sap.upload');

// Rute API untuk generate material code (jika masih digunakan di view)
Route::get('/api/generate-material-code', [ExcelConverterController::class, 'generateMaterialCode'])->name('api.material.generate');

Route::post('/api/activate-qm', [ExcelConverterController::class, 'activateQm'])->name('api.qm.activate');

