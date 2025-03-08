<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLostBookTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lost_book', function (Blueprint $table) {
            $table->increments('book_id'); 
            $table->string('isbn', 20);
            $table->unsignedBigInteger('Member_No');
            $table->date('date_lost');
            $table->timestamps();
            
            // Foreign key to members table
            // Note: 'member_no' in lost_book references 'member_id' in members
            $table->foreign('Member_No') // Changed from 'Member_No' to 'member_no'
                  ->references('member_id')
                  ->on('members')            
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('lost_book');
    }
}