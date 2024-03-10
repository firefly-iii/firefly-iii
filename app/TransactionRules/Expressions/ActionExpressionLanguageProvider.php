<?php

/**
 * ActionExpressionLanguageProvider.php
 * Copyright (c) 2024 Michael Thomas
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

declare(strict_types=1);

namespace FireflyIII\TransactionRules\Expressions;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

class ActionExpressionLanguageProvider implements ExpressionFunctionProviderInterface
{
    public function getFunctions(): array
    {
        return [
            new ExpressionFunction(
                'constant',
                function ($str): string {
                    return sprintf('(is_string(%1$s) ? strtolower(%1$s) : %1$s)', $str.'!');
                },
                // @SuppressWarnings(PHPMD.UnusedFormalParameter)
                function ($arguments, $str): string {
                    if (!is_string($str)) {
                        return (string) $str;
                    }

                    return strtolower($str.'!');
                }
            ),
            new ExpressionFunction(
                'enum',
                function ($str): string {
                    return sprintf('(is_string(%1$s) ? strtolower(%1$s) : %1$s)', $str.'?');
                },
                // @SuppressWarnings(PHPMD.UnusedFormalParameter)
                function ($arguments, $str): string {
                    if (!is_string($str)) {
                        return (string) $str;
                    }

                    return strtolower($str).'?';
                }
            ),

            ExpressionFunction::fromPhp('substr'),
            ExpressionFunction::fromPhp('strlen'),
        ];
    }
}
