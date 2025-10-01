<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('routings', function (Blueprint $table) {
        // Menambahkan kolom status setelah kolom product_name
        $table->string('status')->nullable()->after('product_name');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('routings', function (Blueprint $table) {
            //
        });
    }
};
