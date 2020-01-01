<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class MakeLocationsTable
 */
class MakeLocationsTable extends Migration
{
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('locations');
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'locations', static function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->timestamps();
            $table->softDeletes();

            $table->integer('locatable_id', false, true);
            $table->string('locatable_type', 255);

            $table->decimal('latitude', 24, 12)->nullable();
            $table->decimal('longitude', 24, 12)->nullable();
            $table->smallInteger('zoom_level', false, true)->nullable();
        }
        );
    }
}
