<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMemberTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id('member_id');
            $table->string('firstname', 100);
            $table->string('lastname', 100);
            $table->string('gender', 10);
            $table->string('address', 100);
            $table->string('contact', 100);
            $table->string('type', 100);
            $table->string('designation', 100);
            $table->string('status', 100);
            $table->date('membership_date')->nullable();
            $table->integer('borrowed_books_count')->default(0);
            $table->date('expiry_date')->nullable();
            $table->string('email')->nullable();
            $table->string('id_number')->nullable();
            $table->string('department')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('members');
    }
}
