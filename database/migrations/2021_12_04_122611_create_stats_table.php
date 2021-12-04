<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateStatsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stats', function (Blueprint $table) {
            $table->id();
            $table->enum('model', ['book', 'genre', 'telegramChat', 'telegramUser', 'user', 'userBookAssociation']);
            $table->bigInteger('model_id');
            $table->enum('action', ['created', 'deleted']);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->index(['model', 'model_id']);
            $table->index('action');
        });

        DB::table('books')->orderBy('id')->chunk(1000, function (Collection $data) {
            $stats = [];

            $data->each(function ($item) use (&$stats) {
                $stats[] = [
                    'model' => 'book',
                    'model_id' => $item->id,
                    'action' => 'created',
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at,
                ];
            });

            DB::table('stats')->insertOrIgnore($stats);
        });

        DB::table('genres')->orderBy('id')->chunk(1000, function (Collection $data) {
            $stats = [];

            $data->each(function ($item) use (&$stats) {
                $stats[] = [
                    'model' => 'genre',
                    'model_id' => $item->id,
                    'action' => 'created',
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at,
                ];
            });

            DB::table('stats')->insertOrIgnore($stats);
        });

        DB::table('telegram_chats')->orderBy('id')->chunk(1000, function (Collection $data) {
            $stats = [];

            $data->each(function ($item) use (&$stats) {
                $stats[] = [
                    'model' => 'telegramChat',
                    'model_id' => $item->id,
                    'action' => 'created',
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at,
                ];
            });

            DB::table('stats')->insertOrIgnore($stats);
        });

        DB::table('telegram_users')->orderBy('id')->chunk(1000, function (Collection $data) {
            $stats = [];

            $data->each(function ($item) use (&$stats) {
                $stats[] = [
                    'model' => 'telegramUser',
                    'model_id' => $item->id,
                    'action' => 'created',
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at,
                ];
            });

            DB::table('stats')->insertOrIgnore($stats);
        });

        DB::table('users')->orderBy('id')->chunk(1000, function (Collection $data) {
            $stats = [];

            $data->each(function ($item) use (&$stats) {
                $stats[] = [
                    'model' => 'user',
                    'model_id' => $item->id,
                    'action' => 'created',
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at,
                ];
            });

            DB::table('stats')->insertOrIgnore($stats);
        });

        DB::table('user_book_associations')->orderBy('id')->chunk(1000, function (Collection $data) {
            $stats = [];

            $data->each(function ($item) use (&$stats) {
                $stats[] = [
                    'model' => 'userBookAssociation',
                    'model_id' => $item->id,
                    'action' => 'created',
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at,
                ];
            });

            DB::table('stats')->insertOrIgnore($stats);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('stats');
    }
}
