<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        Schema::create('delegations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('delegator_id')->nullable(); // người ủy quyền 
            $table->unsignedBigInteger('delegate_id')->nullable(); // người được ủy quyền
            $table->foreign('delegator_id')->references('id')->on('users')->onDelete('set null'); 
            $table->foreign('delegate_id')->references('id')->on('users')->onDelete('set null'); 
            $table->date('start_date');
            $table->date('end_date');
            $table->tinyInteger('is_active')->default(0);
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
        });
    }

    
    public function down(): void
    {
        Schema::dropIfExists('delegations');
    }
};
