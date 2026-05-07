<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('plan');
            $table->string('plan_name')->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->string('interval')->nullable(); // month, year
            $table->string('status')->default('active');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->integer('max_galleries')->nullable();
            $table->integer('max_media_per_gallery')->nullable();
            $table->integer('max_media_total')->nullable();
            $table->integer('max_face_searches_per_month')->nullable();
            $table->boolean('face_recognition_enabled')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
