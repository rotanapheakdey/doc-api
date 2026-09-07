<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modify action column to VARCHAR(50) to support 'returned' and future lifecycle actions safely
        DB::statement("ALTER TABLE audit_logs MODIFY COLUMN action VARCHAR(50) NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE audit_logs MODIFY COLUMN action ENUM('uploaded','assigned','dispatched','report_submitted','vdg_signed','dg_signed','archived','returned') NOT NULL");
    }
};
