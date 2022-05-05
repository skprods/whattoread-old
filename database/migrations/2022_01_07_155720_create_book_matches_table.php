<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBookMatchesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('book_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comparing_book_id')->constrained('books')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('matching_book_id')->constrained('books')->cascadeOnUpdate()->cascadeOnDelete();
            $table->tinyInteger('author_score')->default(0);
            $table->float('description_score')->unsigned()->default(0);
            $table->float('total_score')->unsigned()->default(0);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['comparing_book_id', 'matching_book_id']);
            $table->unique(['matching_book_id', 'comparing_book_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('book_matches');
    }
}
