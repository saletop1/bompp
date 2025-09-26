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
        Schema::table('routings', function (Blueprint $table) {
            // Tambahkan kolom baru setelah 'document_number'
            $table->string('document_name')->nullable()->after('document_number');
            $table->string('product_name')->nullable()->after('document_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('routings', function (Blueprint $table) {
            // Hapus kolom jika migrasi di-rollback
            $table->dropColumn(['document_name', 'product_name']);
        });
    }
};
