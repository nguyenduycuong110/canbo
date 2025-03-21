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
        Schema::create('statistics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->date('month'); // Lưu dưới dạng date (ví dụ: 2025-03-01)
            $table->integer('working_days_in_month')->nullable();
            $table->integer('leave_days_with_permission')->nullable();
            $table->integer('leave_days_without_permission')->nullable();
            $table->integer('violation_count')->nullable();
            $table->string('violation_behavior')->nullable();
            $table->string('disciplinary_action')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('statistics');
    }
};
