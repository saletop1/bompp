<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\BomController;
use App\Http\Controllers\RoutingController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ShopDrawingController;

// Rute publik
Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.post');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Rute register (TAMBAHKAN INI)
Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [RegisterController::class, 'register'])->name('register.post');

// Rute yang membutuhkan autentikasi
Route::middleware(['auth'])->group(function () {
    
    // Dashboard/Redirect setelah login - ke converter
    Route::get('/home', function () {
        return redirect()->route('converter.index');
    });
    
    // Converter routes (halaman utama setelah login)
    Route::get('/converter', [BomController::class, 'showMaterialConverter'])->name('converter.index');
    Route::post('/converter/upload', [BomController::class, 'upload'])->name('converter.upload');
    Route::get('/converter/download/{filename}', [BomController::class, 'download'])->name('converter.download');

    // BOM routes
    Route::get('/bom', [BomController::class, 'index'])->name('bom.index');
    Route::post('/bom/upload', [BomController::class, 'processAndStoreFile'])->name('bom.upload');
    Route::get('/bom/download/{filename}', [BomController::class, 'downloadProcessedFile'])->name('bom.download');
    Route::get('/bom/download-routing/{filename}', [BomController::class, 'downloadRoutingTemplate'])->name('bom.download_routing_template');

    // Routing routes
    Route::get('/routing', [RoutingController::class, 'index'])->name('routing.index');

    // Shop Drawing Routes
    Route::get('/shop-drawings', [ShopDrawingController::class, 'index'])->name('shop_drawings.index');
    Route::delete('/shop-drawings/{id}', [ShopDrawingController::class, 'deleteDrawing'])->name('shop_drawings.delete');
    Route::get('/shop-drawings/preview/{id}', [ShopDrawingController::class, 'previewDrawing'])->name('shop_drawings.preview');

    // API routes
    Route::prefix('api')->group(function () {
        // Shop Drawing API
        Route::prefix('shop-drawings')->name('api.shop_drawings.')->group(function () {
            Route::post('/validate', [ShopDrawingController::class, 'validateMaterial'])->name('validate');
            Route::post('/search', [ShopDrawingController::class, 'searchMaterial'])->name('search');
            Route::post('/upload', [ShopDrawingController::class, 'uploadDrawing'])->name('upload');
            Route::post('/upload-multiple', [ShopDrawingController::class, 'uploadMultipleDrawings'])->name('upload_multiple');
            Route::get('/get', [ShopDrawingController::class, 'getShopDrawings'])->name('get_shop_drawings');
            Route::delete('/{id}', [ShopDrawingController::class, 'deleteDrawing'])->name('delete');
            Route::post('/send-email-request', [ShopDrawingController::class, 'sendEmailRequest'])->name('send_email_request');
        });

        // Converter API
        Route::post('/material/generate', [BomController::class, 'generateNextMaterialCode'])->name('api.material.generate');
        Route::post('/sap/stage', [BomController::class, 'stageMaterials'])->name('api.sap.stage');
        Route::post('/sap/activate_and_upload', [BomController::class, 'activateAndUpload'])->name('api.sap.activate_and_upload');
        Route::post('/sap/create_inspection_plan', [BomController::class, 'createInspectionPlan'])->name('api.sap.create_inspection_plan');
        Route::post('/notification/send', [BomController::class, 'sendNotification'])->name('api.notification.send');

        // BOM API
        Route::post('/bom/generate_codes', [BomController::class, 'generateBomMaterialCodes'])->name('api.bom.generate_codes');
        Route::post('/bom/api_find_material', [BomController::class, 'apiFindMaterialCode'])->name('api.bom.api_find_material');
        Route::post('/bom/save_generated_codes', [BomController::class, 'saveGeneratedCodes'])->name('api.bom.save_generated_codes');
        Route::post('/bom/upload', [BomController::class, 'uploadProcessedBom'])->name('api.bom.upload');
        Route::post('/bom/upload_single', [BomController::class, 'uploadSingleBom'])->name('api.bom.upload_single');
        Route::post('/notification/sendBom', [BomController::class, 'sendBomNotification'])->name('api.notification.sendBom');

        // Routing API
        Route::post('/routing/process-file', [RoutingController::class, 'processFile'])->name('routing.processFile');
        Route::post('/routing/save', [RoutingController::class, 'saveRoutings'])->name('routing.save');
        Route::post('/routing/upload-sap', [RoutingController::class, 'uploadToSap'])->name('api.routing.uploadToSap');
        Route::post('/routing/mark-uploaded', [RoutingController::class, 'markAsUploaded'])->name('routing.markAsUploaded');
        Route::post('/routing/update-status', [RoutingController::class, 'updateStatus'])->name('routing.updateStatus');
        Route::post('/routing/delete', [RoutingController::class, 'deleteRoutings'])->name('routing.delete');
        Route::post('/routing/delete-rows', [RoutingController::class, 'deleteRoutingRows'])->name('routing.deleteRows');
        Route::post('/routing/check-doc-name', [RoutingController::class, 'checkDocumentNameExists'])->name('routing.checkDocName');
        Route::post('/routing/check-materials', [RoutingController::class, 'checkMaterialsInExistingDocument'])->name('routing.checkMaterials');
    });
});