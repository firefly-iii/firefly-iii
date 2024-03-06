<?php

/**
 * ExpressionLanguageFactory.php
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

namespace FireflyIII\TransactionRules\Factory;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use FireflyIII\TransactionRules\Expressions\ActionExpressionLanguageProvider;

class ExpressionLanguageFactory
{
    protected static ExpressionLanguage $expressionLanguage;

    private static function constructExpressionLanguage(): ExpressionLanguage
    {
        $expressionLanguage = new ExpressionLanguage();
        $expressionLanguage->registerProvider(new ActionExpressionLanguageProvider());
        return $expressionLanguage;
    }

    public static function get(): ExpressionLanguage
    {
        return self::$expressionLanguage ??= self::constructExpressionLanguage();
    }
}
