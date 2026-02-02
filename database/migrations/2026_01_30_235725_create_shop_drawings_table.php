<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('shop_drawings', function (Blueprint $table) {
            $table->id();
            $table->string('material_code');
            $table->string('plant');
            $table->text('description');
            $table->string('drawing_type')->default('drawing');
            $table->string('dropbox_file_id')->nullable();
            $table->text('dropbox_path')->nullable();
            $table->text('dropbox_share_url')->nullable();
            $table->text('dropbox_direct_url')->nullable();
            $table->string('filename');
            $table->bigInteger('file_size')->default(0);
            $table->string('file_extension')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamp('uploaded_at')->useCurrent();
            $table->timestamps();
            
            $table->index(['material_code', 'plant']);
            $table->index('drawing_type');
            $table->index('user_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('shop_drawings');
    }
};