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
        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('task_id')->nullable(); 
            $table->foreign('task_id')->references('id')->on('tasks');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->date('start_date');
            $table->date('due_date');
            $table->float('completion_date');
            $table->text('output')->nullable();
            $table->tinyInteger('publish')->default(2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluations');
    }
};
