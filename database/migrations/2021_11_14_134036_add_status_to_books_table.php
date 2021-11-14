<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusToBooksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropColumn('series');
            $table->dropColumn('publisher_name');
            $table->dropColumn('publisher_year');
            $table->enum('status', ['moderation', 'active'])->default('moderation')->after('author');

            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropColumn('status');
            $table->string('series')->nullable();
            $table->string('publisher_name')->nullable();
            $table->string('publisher_year')->nullable();
        });
    }
}
