<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('words', function (Blueprint $table) {
            $table->id();
            $table->string('word', 100);
            $table->integer('parent_id')->unsigned()->nullable();
            $table->set('type', ['част', 'межд', 'прл', 'прч', 'сущ', 'нар', 'гл', 'дееп', 'союз', 'предик', 'предл', 'ввод', 'мест', 'числ'])->nullable();
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
            $table->timestamp('created_at')->useCurrent();

            $table->index('word');
            $table->index('parent_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('words');
    }
}
