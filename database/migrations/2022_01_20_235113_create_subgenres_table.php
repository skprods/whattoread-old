<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateSubgenresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('subgenres', function (Blueprint $table) {
            $table->foreignId('parent_id')->constrained('genres')->cascadeOnDelete();
            $table->foreignId('child_id')->constrained('genres')->cascadeOnDelete();

            $table->unique(['parent_id', 'child_id']);
        });

        $subgenres = [];
        DB::table('genres')
            ->where('parent_id', '!=', 0)
            ->orderBy('id')
            ->each(function ($genre) use (&$subgenres) {
                $subgenres[] = [
                    'parent_id' => $genre->parent_id,
                    'child_id' => $genre->id,
                ];
            });
        DB::table('subgenres')->insert($subgenres);

        Schema::table('genres', function (Blueprint $table) {
            $table->dropColumn('parent_id');
            DB::statement(DB::raw("
                ALTER TABLE `genres`
                MODIFY COLUMN `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
            "));
            DB::statement(DB::raw("
                ALTER TABLE `genres`
                MODIFY COLUMN `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            "));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('genres', function (Blueprint $table) {
            $table->bigInteger('parent_id')->nullable()->after('id');
        });

        DB::table('subgenres')->orderBy('parent_id')->each(function ($subgenre) {
            DB::table('genres')->where('id', $subgenre->child_id)->update([
                'parent_id' => $subgenre->parent_id,
            ]);
        });

        Schema::dropIfExists('subgenres');
    }
}
