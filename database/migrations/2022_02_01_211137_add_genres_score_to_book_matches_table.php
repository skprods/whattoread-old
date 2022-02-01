<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGenresScoreToBookMatchesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('book_matches', function (Blueprint $table) {
            $table->smallInteger('genres_score')->unsigned()->default(0)->after('author_score');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('book_matches', function (Blueprint $table) {
            $table->dropColumn('genres_score');
        });
    }
}
