<?php
/**
 * 2017_08_20_062014_changes_for_v470.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class ChangesForV470
 */
class ChangesForV470 extends Migration
{
    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('journal_links');
        Schema::dropIfExists('link_types');
    }

    /**
     * Run the migrations.
     *
     * @SuppressWarnings(PHPMD.ShortMethodName)
     */
    public function up()
    {
        if (!Schema::hasTable('link_types')) {
            Schema::create(
                'link_types',
                function (Blueprint $table) {
                    $table->increments('id');
                    $table->timestamps();
                    $table->softDeletes();
                    $table->string('name');
                    $table->string('outward');
                    $table->string('inward');
                    $table->boolean('editable');

                    $table->unique(['name', 'outward', 'inward']);
                }
            );
        }

        if (!Schema::hasTable('journal_links')) {
            Schema::create(
                'journal_links',
                function (Blueprint $table) {
                    $table->increments('id');
                    $table->timestamps();
                    $table->integer('link_type_id', false, true);
                    $table->integer('source_id', false, true);
                    $table->integer('destination_id', false, true);
                    $table->text('comment')->nullable();

                    $table->foreign('link_type_id')->references('id')->on('link_types')->onDelete('cascade');
                    $table->foreign('source_id')->references('id')->on('transaction_journals')->onDelete('cascade');
                    $table->foreign('destination_id')->references('id')->on('transaction_journals')->onDelete('cascade');

                    $table->unique(['link_type_id', 'source_id', 'destination_id']);
                }
            );
        }
    }
}
