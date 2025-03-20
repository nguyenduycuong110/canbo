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
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // user_catalogues:index
            $table->string('module'); //user_catalogues, posts, users, permissions, products
            $table->integer('value'); // Giá trị nhị phân của quyền :  
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->integer('publish')->default(2);
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();
            $table->unique(['module', 'value']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
