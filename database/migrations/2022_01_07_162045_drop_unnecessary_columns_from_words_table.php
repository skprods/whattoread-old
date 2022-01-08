<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropUnnecessaryColumnsFromWordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('words', function (Blueprint $table) {
            $table->dropColumn('type_sub');
            $table->dropColumn('type_ssub');
            $table->dropColumn('plural');
            $table->dropColumn('gender');
            $table->dropColumn('wcase');
            $table->dropColumn('comp');
            $table->dropColumn('soul');
            $table->dropColumn('transit');
            $table->dropColumn('perfect');
            $table->dropColumn('face');
            $table->dropColumn('kind');
            $table->dropColumn('time');
            $table->dropColumn('inf');
            $table->dropColumn('vozv');
            $table->dropColumn('nakl');
            $table->dropColumn('short');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('words', function (Blueprint $table) {
            $table->enum('type_sub', ['поряд', 'кол', 'собир', 'неопр', 'врем', 'обст', 'опред', 'счет', 'неизм'])->nullable();
            $table->enum('type_ssub', ['кач', 'спос', 'степ', 'места', 'напр', 'врем', 'цель', 'причин'])->nullable();
            $table->boolean('plural')->nullable();
            $table->enum('gender', ['муж','жен','ср','общ'])->nullable();
            $table->enum('wcase', ['им','род','дат','вин','тв','пр','зват','парт','мест'])->nullable();
            $table->enum('comp', ['сравн','прев'])->nullable();
            $table->boolean('soul')->nullable();
            $table->enum('transit', ['перех','непер','пер/не'])->nullable();
            $table->boolean('perfect')->nullable();
            $table->enum('face', ['1-е','2-е','3-е','безл'])->nullable();
            $table->enum('kind', ['1вид','2вид'])->nullable();
            $table->enum('time', ['прош','наст','буд'])->nullable();
            $table->boolean('inf')->nullable();
            $table->boolean('vozv')->nullable();
            $table->enum('nakl', ['пов','страд'])->nullable();
            $table->boolean('short')->nullable();
        });
    }
}
