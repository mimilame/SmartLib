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
        Schema::create('borrow_details', function (Blueprint $table) {
            $table->id('borrow_details_id');
            $table->integer('book_id');
            $table->integer('borrow_id');
            $table->string('borrow_status', 50);
            $table->string('date_return', 100)->default('');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('borrow_details');
    }
};
