<?php

/**
 * ActionExpressionEvaluator.php
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

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\SyntaxError;

class ActionExpressionEvaluator
{
    private static array $NAMES = array("transaction");

    private ExpressionLanguage $expressionLanguage;
    private string $expr;
    private bool $isExpression;
    private ?SyntaxError $validationError;

    public function __construct(ExpressionLanguage $expressionLanguage, string $expr)
    {
        $this->expressionLanguage = $expressionLanguage;
        $this->expr = $expr;

        $this->isExpression = self::isExpression($expr);
        $this->validationError = $this->validate();
    }

    private static function isExpression(string $expr): bool
    {
        return str_starts_with($expr, "=");
    }

    private function validate(): ?SyntaxError
    {
        if (!$this->isExpression) {
            return null;
        }

        try {
            $this->lint();
            return null;
        } catch (SyntaxError $e) {
            return $e;
        }
    }

    private function lintExpression(string $expr): void
    {
        $this->expressionLanguage->lint($expr, self::$NAMES);
    }

    private function lint(): void
    {
        if (!$this->isExpression) {
            return;
        }

        $this->lintExpression(substr($this->expr, 1));
    }

    public function isValid(): bool
    {
        return $this->validationError === null;
    }

    public function getValidationError()
    {
        return $this->validationError;
    }

    private function evaluateExpression(string $expr, array $journal): string
    {
        $result = $this->expressionLanguage->evaluate($expr, [
            "transaction" => $journal
        ]);
        return strval($result);
    }

    public function evaluate(array $journal): string
    {
        if (!$this->isExpression) {
            return $this->expr;
        }

        return $this->evaluateExpression(substr($this->expr, 1), $journal);
    }
}
