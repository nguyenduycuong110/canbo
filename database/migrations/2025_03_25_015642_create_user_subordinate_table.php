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
        Schema::create('user_subordinate', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('manager_id'); // ID của người quản lý (đội phó)
            $table->unsignedBigInteger('subordinate_id'); // ID của người bị quản lý (công chức)
            $table->timestamps();
            
            $table->foreign('manager_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('subordinate_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_subordinate');
    }
};
