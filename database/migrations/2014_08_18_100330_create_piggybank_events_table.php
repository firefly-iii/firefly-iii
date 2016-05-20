<?php
declare(strict_types = 1);



use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * @SuppressWarnings(PHPMD.ShortMethodName)
 *
 * Class CreatePiggybankEventsTable
 *
 */
class CreatePiggybankEventsTable extends Migration
{

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('piggybank_events');
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'piggybank_events', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->integer('piggybank_id')->unsigned();
            $table->integer('transaction_journal_id')->unsigned()->nullable();

            $table->date('date');
            $table->decimal('amount', 10, 2);

            // connect instance to piggybank.
            $table->foreign('piggybank_id')->references('id')->on('piggybanks')->onDelete('cascade');

            // connect to journal:
            $table->foreign('transaction_journal_id')->references('id')->on('transaction_journals')->onDelete('set null');
        }
        );
    }

}
