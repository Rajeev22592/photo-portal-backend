<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_id')->constrained()->cascadeOnDelete();
            $table->integer('x');
            $table->integer('y');
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->decimal('confidence', 5, 4)->nullable();
            $table->json('bounding_box')->nullable();
            $table->text('embedding')->nullable(); // for face search - optional base64 or json
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faces');
    }
};
