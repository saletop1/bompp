<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('document_sequences', function (Blueprint $table) {
            $table->string('prefix')->primary();
            $table->unsignedBigInteger('last_sequence')->default(0);
        });
        // Inisialisasi nomor urut untuk routing
        DB::table('document_sequences')->insert(['prefix' => 'RPP']);
    }
    public function down(): void {
        Schema::dropIfExists('document_sequences');
    }
};
