<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTelegramChatsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('telegram_chats', function (Blueprint $table) {
            $table->id();
            $table->string('chat_id')->unique();
            $table->string('title');
            $table->timestamps();
        });

        Schema::table('telegram_messages', function (Blueprint $table) {
            // TODO: добавить отдельной миграцией cascadeOnUpdate
            $table->foreignId('telegram_chat_id')->nullable()->after('id')->constrained();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('telegram_messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('telegram_chat_id');
        });

        Schema::dropIfExists('telegram_chats');
    }
}
