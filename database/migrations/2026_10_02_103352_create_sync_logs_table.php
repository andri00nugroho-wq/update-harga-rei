<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('status');
            $table->unsignedInteger('total_data')->default(0);
            $table->text('result_path')->nullable();
            $table->text('message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_logs');
    }
};