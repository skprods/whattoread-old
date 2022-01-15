<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserBookAssociationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_book_associations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained();
            $table->foreignId('user_id')->nullable()->constrained();
            $table->foreignId('telegram_user_id')->nullable()->constrained();
            $table->string('association');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::table('book_associations', function (Blueprint $table) {
            $table->integer('total')->default(1)->after('association');
            $table->unique(['book_id', 'association']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('book_associations', function (Blueprint $table) {
            $table->dropUnique(['book_id', 'association']);
            $table->dropColumn('total');
        });

        Schema::dropIfExists('user_book_associations');
    }
}
