<?php
declare(strict_types = 1);



use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * @SuppressWarnings(PHPMD.ShortMethodName)
 *
 * Class CreatePiggyInstance
 *
 */
class CreatePiggybankRepetitionsTable extends Migration
{

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('piggybank_repetitions');
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'piggybank_repetitions', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->integer('piggybank_id')->unsigned();
            $table->date('startdate')->nullable();
            $table->date('targetdate')->nullable();
            $table->decimal('currentamount', 10, 2);

            $table->unique(['piggybank_id', 'startdate', 'targetdate']);

            // connect instance to piggybank.
            $table->foreign('piggybank_id')->references('id')->on('piggybanks')->onDelete('cascade');
        }
        );
    }

}
