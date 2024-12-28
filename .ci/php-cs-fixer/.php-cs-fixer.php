<?php
/*
 * .php-cs-fixer.php
 * Copyright (c) 2022 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

$current = __DIR__;

$paths = [
    $current . '/../../app',
    $current . '/../../config',
    $current . '/../../database',
    $current . '/../../routes',
    $current . '/../../tests',
    $current . '/../../resources/lang/en_US',
];

$finder = PhpCsFixer\Finder::create()
                           ->in($paths);


$config = new PhpCsFixer\Config();
$config->setParallelConfig(ParallelConfigFactory::detect());
return $config->setRules(
    [
        // rule sets
        '@PHP83Migration'               => true,
        '@PhpCsFixer'                   => true,
        '@PhpCsFixer:risky'             => true,
        '@PSR12'                        => true,
        '@PSR12:risky'                  => true,
        'declare_strict_types'          => true,
        'strict_param'                  => true,
        'no_unused_imports'             => true,
        'single_space_around_construct' => true,
        'statement_indentation'         => true,
        'void_return'                   => true,

        // disabled rules
        'native_function_invocation'    => false, // annoying
        'php_unit_data_provider_name'   => false, // bloody annoying long test names
        'static_lambda'                 => false, // breaks the Response macro for API's.
        'phpdoc_summary'                => false, // annoying.
        'comment_to_phpdoc'             => false, // breaks phpstan lines in combination with PHPStorm.
        'type_declaration_spaces'       => false,
        'cast_spaces'                   => false,

        // complex rules
        'array_syntax'                  => ['syntax' => 'short'],
        'binary_operator_spaces'        => [
            'default'   => 'at_least_single_space',
            'operators' => [
                '=>'  => 'align_single_space_by_scope',
                '='   => 'align_single_space_minimal_by_scope',
                '??=' => 'align_single_space_minimal_by_scope',
            ],
        ],
    ])
              ->setFinder($finder);
