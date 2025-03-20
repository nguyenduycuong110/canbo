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
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('team_id')->nullable(); 
            $table->foreign('team_id')->references('id')->on('teams') ->onDelete('cascade');
            $table->unsignedBigInteger('unit_id')->nullable(); 
            $table->foreign('unit_id')->references('id')->on('units') ->onDelete('cascade');
            $table->unsignedBigInteger('department_id')->nullable(); 
            $table->foreign('department_id')->references('id')->on('departments') ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('team_id');
            $table->dropColumn('unit_id');
            $table->dropColumn('department_id');
        });
    }
};
