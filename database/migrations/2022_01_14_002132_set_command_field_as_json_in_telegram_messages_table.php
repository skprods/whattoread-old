<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SetCommandFieldAsJsonInTelegramMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('telegram_messages', function (Blueprint $table) {
            $table->json('commandInfo')->nullable()->after('command');
        });

        DB::table('telegram_messages')->orderBy('id')->each(function ($data) {
            $data = (array) $data;
            $data['commandInfo'] = ['command' => $data['command'], 'param' => null, 'page' => null];
            DB::table('telegram_messages')->where('id', $data['id'])->update($data);
        });

        Schema::table('telegram_messages', function (Blueprint $table) {
            $table->json('commandInfo')->change();
            $table->dropColumn('command');
        });

        Schema::table('telegram_messages', function (Blueprint $table) {
            $table->renameColumn('commandInfo', 'command');
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
            $table->renameColumn('command', 'commandInfo');
        });

        Schema::table('telegram_messages', function (Blueprint $table) {
            $table->string('command', 45)->nullable();
        });

        DB::table('telegram_messages')->orderBy('id')->each(function ($data) {
            $data = (array) $data;
            $data['command'] = $data['commandInfo']['command'];
            DB::table('telegram_messages')->where('id', $data['id'])->update($data);
        });

        Schema::table('telegram_messages', function (Blueprint $table) {
            $table->dropColumn('commandInfo');
        });
    }
}
