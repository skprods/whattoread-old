<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexToBookDescriptionFrequenciesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('book_description_frequencies', function (Blueprint $table) {
            $table->index(['book_id', 'word_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('book_description_frequencies', function (Blueprint $table) {
            $table->dropIndex(['book_id', 'word_id']);
        });
    }
}
