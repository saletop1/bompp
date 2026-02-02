<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material_images', function (Blueprint $table) {
            $table->id();
            $table->string('material_code')->index();
            $table->string('plant')->nullable();
            $table->string('description')->nullable();
            $table->string('dropbox_file_id')->nullable();
            $table->string('dropbox_path')->nullable();
            $table->string('dropbox_share_url')->nullable();
            $table->string('filename')->nullable();
            $table->integer('file_size')->nullable();
            $table->string('file_type')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['material_code', 'plant', 'filename']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_images');
    }
};