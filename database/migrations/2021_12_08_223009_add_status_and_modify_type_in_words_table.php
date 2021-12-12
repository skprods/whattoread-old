<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddStatusAndModifyTypeInWordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('words', function (Blueprint $table) {
            $table->enum('status', ['moderation', 'active'])->default('moderation')->after('short');
        });

        DB::statement(DB::raw("
            ALTER TABLE `words`
            MODIFY COLUMN `type` set(
                'част','межд','прл','прч','сущ','нар','гл','дееп','союз','предик','предл','ввод','мест','числ','фраз'
            ) COLLATE utf8mb4_unicode_ci DEFAULT NULL
        "));
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement(DB::raw("
            ALTER TABLE `words`
            MODIFY COLUMN `type` set(
                'част','межд','прл','прч','сущ','нар','гл','дееп','союз','предик','предл','ввод','мест','числ'
            ) COLLATE utf8mb4_unicode_ci DEFAULT NULL
        "));

        Schema::table('words', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
}
