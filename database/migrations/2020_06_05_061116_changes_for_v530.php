<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Class ChangesForV530
 */
class ChangesForV530 extends Migration
{
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(
            'users', static function (Blueprint $table) {
            $table->dropColumn('provider');
        }
        );
        Schema::dropIfExists('object_groupables');
        Schema::dropIfExists('object_groups');
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(
            'users', static function (Blueprint $table) {
            $table->string('provider', 50)->nullable();
        }
        );

        if (!Schema::hasTable('object_groups')) {
            Schema::create(
                'object_groups', static function (Blueprint $table) {
                $table->increments('id');
                $table->timestamps();
                $table->softDeletes();
                $table->string('title', 255);
                $table->mediumInteger('order', false, true)->default(0);
            }
            );
        }

        if (!Schema::hasTable('object_groupables')) {
            Schema::create(
                'object_groupables', static function (Blueprint $table) {
                $table->integer('object_group_id');
                $table->integer('object_groupable_id', false, true);
                $table->string('object_groupable_type', 255);
            }
            );
        }
    }
}
