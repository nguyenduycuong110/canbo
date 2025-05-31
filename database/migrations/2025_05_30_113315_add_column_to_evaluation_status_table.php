<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::table('evaluation_status', function (Blueprint $table) {
            $table->unsignedBigInteger('delegate_id')->nullable(); 
            $table->foreign('delegate_id')->references('id')->on('users')->onDelete('set null'); 
        });
    }

    
    public function down(): void
    {
        Schema::table('evaluation_status', function (Blueprint $table) {
            $table->dropColumn('delegate_id');
        });
    }
};
