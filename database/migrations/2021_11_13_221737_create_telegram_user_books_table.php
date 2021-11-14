<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTelegramUserBooksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('telegram_user_books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('telegram_user_id')->constrained()->onDelete('CASCADE');
            $table->foreignId('book_id')->constrained()->onDelete('CASCADE');
            $table->tinyInteger('rating');
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
        Schema::dropIfExists('telegram_user_books');
    }
}
