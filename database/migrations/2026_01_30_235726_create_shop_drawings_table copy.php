<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Jika Anda menggunakan MySQL, ini akan memastikan hanya nilai tertentu yang diterima
        // Note: Ini hanya berfungsi di MySQL dengan CHECK constraint yang diaktifkan
        // Untuk Laravel, validasi sebaiknya dilakukan di Controller/Request
        
        // Atau, jika Anda ingin mengubah ENUM (jika field drawing_type adalah ENUM)
        // Hapus dulu, lalu buat ulang dengan nilai baru
        // Tapi ini RISKY untuk production data
        
        // Alternatif: Tambahkan kolom untuk menandai versi data
        Schema::table('shop_drawings', function (Blueprint $table) {
            $table->string('data_version')->nullable()->default('v2')->after('uploaded_at');
            $table->string('upload_session_id')->nullable()->after('data_version');
        });
        
        // Atau jika Anda ingin menambahkan kolom untuk multiple files
        Schema::table('shop_drawings', function (Blueprint $table) {
            $table->integer('file_sequence')->nullable()->default(1)->after('revision');
            $table->string('batch_id')->nullable()->after('file_sequence');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shop_drawings', function (Blueprint $table) {
            $table->dropColumn(['data_version', 'upload_session_id']);
        });
        
        Schema::table('shop_drawings', function (Blueprint $table) {
            $table->dropColumn(['file_sequence', 'batch_id']);
        });
    }
};