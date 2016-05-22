<?php
declare(strict_types = 1);


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Class ChangesForV380
 */
class ChangesForV380 extends Migration
{
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('export_jobs');
        Schema::drop('journal_meta');
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        // extend transaction journals:
        Schema::table(
            'transaction_journals', function (Blueprint $table) {
            $table->date('process_date')->nullable()->after('book_date');
        }
        );

        // new table "export_jobs"
        Schema::create(
            'export_jobs', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->integer('user_id')->unsigned();
            $table->string('key', 12)->unique();
            $table->string('status', 45);

            // connect rule groups to users
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

        }
        );

        // new table for transaction journal meta, "journal_meta"
        Schema::create(
            'journal_meta', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->integer('transaction_journal_id')->unsigned();
            $table->string('name');
            $table->text('data');

            $table->unique(['transaction_journal_id', 'name']);

            // link to transaction journal
            $table->foreign('transaction_journal_id')->references('id')->on('transaction_journals')->onDelete('cascade');
        }
        );
    }
}
