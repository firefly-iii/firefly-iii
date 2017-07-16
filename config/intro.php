<?php
/**
 * intro.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

/*
 * Always make sure intro is the first element (if any) and outro is the last one.
 */

return [
    'index'       => [
        'intro'          => [],
        'accounts-chart' => ['selector' => '#accounts-chart'],
        'box_out_holder' => ['selector' => '#box_out_holder'],
        'help'           => ['selector' => '#help', 'position' => 'bottom'],
        'sidebar-toggle' => ['selector' => '#sidebar-toggle', 'position' => 'bottom'],
        'outro'          => [],
    ],
    'rules_index' => [
        'intro'          => [],
        'new_rule_group' => ['selector' => '#new_rule_group'],
        'new_rule' => ['selector' => '.new_rule'],
        'prio_buttons'   => ['selector' => '.prio_buttons'],
        'test_buttons'   => ['selector' => '.test_buttons'],
        'rule-triggers'  => ['selector' => '.rule-triggers'],
        'outro'          => [],

    ],
];