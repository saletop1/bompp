<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('routings', function (Blueprint $table) {
            // Tambahkan kolom timestamp, NULL berarti belum di-upload
            $table->timestamp('uploaded_to_sap_at')->nullable()->after('operations');
        });
    }

    public function down(): void
    {
        Schema::table('routings', function (Blueprint $table) {
            $table->dropColumn('uploaded_to_sap_at');
        });
    }
};
