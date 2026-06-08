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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('uploaded_by_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('assigned_department_id')->nullable()->constrained('departments')->onDelete('set null');
            $table->string('control_no')->unique();
            $table->string('title');
            $table->string('file_path');
            $table->text('file_dept_comment')->nullable();

            //workflow
            $table->enum('status',[
                'pending_dg_init',
                'dg_directed',
                'processing_dept',
                'pending_vdg',
                'dg_approved',
                'dg_signed',
                'completed_archive'
            ])->default('pending_dg_init');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
