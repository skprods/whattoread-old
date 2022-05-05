<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateForeingKeysToCascade extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('telegram_messages', function (Blueprint $table) {
            $table->dropForeign(['telegram_user_id']);
            $table->dropForeign(['telegram_chat_id']);

            $table->foreign('telegram_user_id')
                ->references('id')
                ->on('telegram_users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreign('telegram_chat_id')
                ->references('id')
                ->on('telegram_chats')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        });

        Schema::table('user_book_associations', function (Blueprint $table) {
            $table->dropForeign(['book_id']);
            $table->dropForeign(['user_id']);
            $table->dropForeign(['telegram_user_id']);

            $table->foreign('book_id')
                ->references('id')
                ->on('books')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreign('telegram_user_id')
                ->references('id')
                ->on('telegram_users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        });

        Schema::table('book_dictionaries', function (Blueprint $table) {
            DB::statement(DB::raw("
                ALTER TABLE `book_dictionaries`
                MODIFY COLUMN `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
            "));
            DB::statement(DB::raw("
                ALTER TABLE `book_dictionaries`
                MODIFY COLUMN `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            "));

            $table->dropForeign(['book_id']);

            $table->foreign('book_id')
                ->references('id')
                ->on('books')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
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
            $table->dropForeign(['telegram_user_id']);
            $table->foreign('telegram_user_id')
                ->references('id')
                ->on('telegram_users');
        });
    }
}
