<?php
declare(strict_types = 1);



use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * @SuppressWarnings(PHPMD.ShortMethodName)\
 *
 *
 * Class CreateLimitsTable
 *
 */
class CreateLimitsTable extends Migration
{

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('limits');
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'limits', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->integer('component_id')->unsigned();
            $table->date('startdate');
            $table->decimal('amount', 10, 2);
            $table->boolean('repeats');
            $table->enum('repeat_freq', ['daily', 'weekly', 'monthly', 'quarterly', 'half-year', 'yearly']);

            $table->unique(['component_id', 'startdate', 'repeat_freq'], 'unique_ci_combi');

            // connect component
            $table->foreign('component_id')->references('id')->on('components')->onDelete('cascade');
        }
        );
    }

}
