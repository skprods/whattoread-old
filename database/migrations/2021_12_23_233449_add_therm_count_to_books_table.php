<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddThermCountToBooksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('books', function (Blueprint $table) {
            $table->integer('therms_count')->after('words_count')->unsigned()->default(0);
        });

        $frequencies = DB::select(
            DB::raw("select count(*) as total, book_id from therm_frequencies tf group by book_id;")
        );

        foreach ($frequencies as $frequency) {
            DB::table('books')
                ->where('id', $frequency->book_id)
                ->update(['therms_count' => $frequency->total]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropColumn('therms_count');
        });
    }
}
