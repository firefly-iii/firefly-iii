<?php
/**
 * 2016_01_11_193428_changes_for_v370.php
 * Copyright (C) 2016 Sander Dorigo
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Class ChangesForV370
 */
class ChangesForV370 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // new table "rule_groups"
        Schema::create(
            'rule_groups', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->softDeletes();
            $table->integer('user_id')->unsigned();
            $table->unsignedSmallInteger('order');
            $table->string('title', 255);
            $table->text('description');
            $table->unsignedTinyInteger('active')->default(1);

            // connect rule groups to users
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // order must be unique for rule group:
            $table->unique(['user_id', 'order']);
        }
        );


        // new table "rules":
        Schema::create(
            'rules', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->softDeletes();
            $table->integer('user_id')->unsigned();
            $table->integer('rule_group_id')->unsigned();
            $table->unsignedSmallInteger('order');
            $table->unsignedTinyInteger('active')->default(1);
            $table->unsignedTinyInteger('stop_processing')->default(0);

            $table->string('title', 255);
            $table->text('description');


            // connect rules to users
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // connect rules to rule groups
            $table->foreign('rule_group_id')->references('id')->on('rule_groups')->onDelete('cascade');

            // order must be unique for rules:
            $table->unique(['user_id', 'order']);
        }
        );


        // new table "rule_triggers"
        Schema::create(
            'rule_triggers', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->integer('rule_id')->unsigned();
            $table->unsignedSmallInteger('order');
            $table->unsignedTinyInteger('active')->default(1);
            $table->unsignedTinyInteger('stop_processing')->default(0);

            $table->string('trigger_type', 50);
            $table->string('trigger_value', 255);



            // order must be unique for rule triggers:
            $table->unique(['rule_id', 'order']);

            // connect rule triggers to rules
            $table->foreign('rule_id')->references('id')->on('rules')->onDelete('cascade');
        }
        );

        // new table "rule_actions"
        Schema::create(
            'rule_actions', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->integer('rule_id')->unsigned();
            $table->unsignedSmallInteger('order');
            $table->unsignedTinyInteger('active')->default(1);
            $table->unsignedTinyInteger('stop_processing')->default(0);

            $table->string('action_field', 50);
            $table->string('action', 50);
            $table->string('action_value', 255);





            // connect rule actions to rules
            $table->foreign('rule_id')->references('id')->on('rules')->onDelete('cascade');

            // order must be unique for rule triggers:
            $table->unique(['rule_id', 'order']);
        }
        );

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('rule_actions');
        Schema::drop('rule_triggers');
        Schema::drop('rules');
        Schema::drop('rule_groups');

    }
}
