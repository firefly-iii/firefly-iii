<?php
declare(strict_types = 1);



use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * @SuppressWarnings(PHPMD.ShortMethodName)
 *
 * Class CreateAccountTypesTable
 *
 */
class CreateAccountTypesTable extends Migration
{

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('account_types');
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'account_types', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->string('type', 30);
            $table->boolean('editable');

            $table->unique('type');
        }
        );
    }

}
