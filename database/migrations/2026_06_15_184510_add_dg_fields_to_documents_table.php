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
        Schema::table('documents', function (Blueprint $blueprint) {
            // Add the missing executive note field (nullable because it is optional)
            $blueprint->text('dg_note')->nullable()->after('file_dept_comment');

            // Add the storage pointer tracking column for the automated PDF template
            $blueprint->string('directive_file_path')->nullable()->after('dg_note');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $blueprint) {
            $blueprint->dropColumn(['dg_note', 'directive_file_path']);
        });
    }
};
