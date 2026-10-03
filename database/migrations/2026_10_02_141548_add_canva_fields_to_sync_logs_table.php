<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sync_logs', function (Blueprint $table) {
            $table->string('job_id')
                ->nullable()
                ->unique()
                ->after('total_data');

            $table->string('design_id')
                ->nullable()
                ->after('job_id');

            $table->text('result_url')
                ->nullable()
                ->after('design_id');

            $table->text('edit_url')
                ->nullable()
                ->after('result_url');

            $table->text('view_url')
                ->nullable()
                ->after('edit_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sync_logs', function (Blueprint $table) {
            $table->dropUnique([
                'sync_logs_job_id_unique',
            ]);

            $table->dropColumn([
                'job_id',
                'design_id',
                'result_url',
                'edit_url',
                'view_url',
            ]);
        });
    }
};