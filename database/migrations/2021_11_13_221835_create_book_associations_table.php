<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBookAssociationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('book_associations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->onDelete('CASCADE');
            $table->string('association');
            $table->timestamps();

            $table->index('book_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('book_associations');
    }
}
