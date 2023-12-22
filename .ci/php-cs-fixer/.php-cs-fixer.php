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

$current = __DIR__;

$paths = [
    $current . '/../../app',
    $current . '/../../config',
    $current . '/../../database',
    $current . '/../../routes',
    $current . '/../../tests',
    $current . '/../../resources/lang',
];

$finder = PhpCsFixer\Finder::create()
                           ->in($paths);


$config = new PhpCsFixer\Config();
return $config->setRules([
                             'no_unused_imports'             => true,
                             '@PhpCsFixer'                   => true,
                             '@PHP83Migration'               => true,
                             '@PhpCsFixer:risky'             => true,
                             '@PSR12:risky'                  => true,
                             'declare_strict_types'          => true,
                             'strict_param'                  => true,
                             'comment_to_phpdoc'             => false, // breaks phpstan lines in combination with PHPStorm.
                             'array_syntax'                  => ['syntax' => 'short'],
                             'native_function_invocation'    => false, // annoying
                             'php_unit_data_provider_name'   => false, // bloody annoying long test names
                             'static_lambda'                 => false, // breaks the Response macro for API's.
                             'phpdoc_summary'                => false, // annoying.
                             'single_space_around_construct' => [
                                 'constructs_followed_by_a_single_space' => [
                                     'protected',
                                 ],
                             ],
                             'statement_indentation'         => true,
                             'type_declaration_spaces'       => false,
                             'cast_spaces'                   => false,
                             'binary_operator_spaces'        => false,
                             'void_return'                   => true,
                         ])
              ->setFinder($finder);
