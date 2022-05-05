<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddProperNounTypeToWordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement(DB::raw("
            ALTER TABLE `words`
            MODIFY COLUMN `type` enum(
                'част','межд','прл','прч','сущ','нар','гл','дееп','союз','предик','предл','ввод','мест','числ','фраз','собс'
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
                'част','межд','прл','прч','сущ','нар','гл','дееп','союз','предик','предл','ввод','мест','числ','фраз'
            ) COLLATE utf8mb4_unicode_ci DEFAULT NULL
        "));
    }
}
