<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBookDictionariesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('book_dictionaries', function (Blueprint $table) {
            $table->id();
            // TODO: добавить отдельной миграцией cascadeOnUpdate
            $table->foreignId('book_id')->constrained();
            $table->json('words');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('book_dictionaries');
    }
}
