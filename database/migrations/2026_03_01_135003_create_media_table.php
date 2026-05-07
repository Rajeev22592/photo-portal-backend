<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gallery_id')->constrained()->cascadeOnDelete();
            $table->string('path'); // storage path
            $table->string('url')->nullable(); // public URL
            $table->string('thumbnail_path')->nullable();
            $table->string('thumbnail_url')->nullable();
            $table->string('type')->default('photo'); // photo, video
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->boolean('has_face')->default(false);
            $table->boolean('face_processed')->default(false);
            $table->integer('faces_detected')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
