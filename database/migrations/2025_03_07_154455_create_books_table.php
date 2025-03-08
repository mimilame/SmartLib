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
        Schema::create('books', function (Blueprint $table) {
            $table->id('book_id');
            $table->string('book_title', 100);
            $table->unsignedBigInteger('category_id');
            $table->string('author', 50);
            $table->integer('book_copies');
            $table->string('book_pub', 100);
            $table->string('publisher_name', 100);
            $table->string('isbn', 50);
            $table->integer('copyright_year');
            $table->string('date_receive', 20);
            $table->dateTime('date_added');
            $table->string('status', 30);
            $table->timestamps();
        
            $table->foreign('category_id')->references('category_id')->on('categories')->onDelete('cascade');
        });
        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('books');
    }
};
