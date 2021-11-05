<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBooksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('series')->nullable();
            $table->string('publisher_name')->nullable();
            $table->string('publisher_year')->nullable();
            $table->string('author');
            $table->string('category')->nullable();
            $table->string('shop_url')->nullable();
            $table->enum('shop_name', ['Буквоед']);
            $table->integer('shop_book_id');
            $table->timestamps();

            $table->index('title');
            $table->index('publisher_name');
            $table->index('author');
            $table->index('category');
            $table->index('shop_name');
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
}
