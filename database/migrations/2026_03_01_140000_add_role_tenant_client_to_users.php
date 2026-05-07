<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('tenant')->after('email');
            $table->unsignedBigInteger('tenant_id')->nullable()->after('role');
            $table->unsignedBigInteger('client_id')->nullable()->after('tenant_id');
            $table->string('phone')->nullable()->after('password');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
            $table->foreign('client_id')->references('id')->on('clients')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropForeign(['client_id']);
            $table->dropColumn(['role', 'tenant_id', 'client_id', 'phone']);
        });
    }
};
