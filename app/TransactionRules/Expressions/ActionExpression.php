<?php

/**
 * ActionExpression.php
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

class ActionExpression
{
    private static array                $NAMES
        = [
            //        'transaction_group_id',
            //        'user_id',
            //        'user_group_id',
            'created_at',
            'updated_at',
            'transaction_group_title',
            'group_created_at',
            'group_updated_at',
            //        'transaction_journal_id',
            //        'transaction_type_id',
            'description',
            'date',
            //        'order',
            'transaction_type_type',
            //        'source_transaction_id',
            'source_account_id',
            //        'reconciled',
            'amount',
            //        'currency_id',
            'currency_code',
            'currency_name',
            'currency_symbol',
            'currency_decimal_places',
            'foreign_amount',
            //        'foreign_currency_id',
            'foreign_currency_code',
            'foreign_currency_name',
            'foreign_currency_symbol',
            'foreign_currency_decimal_places',
            'destination_account_id',
            'source_account_name',
            'source_account_iban',
            'source_account_type',
            'destination_account_name',
            'destination_account_iban',
            'destination_account_type',
            'category_id',
            'category_name',
            'budget_id',
            'budget_name',
            'tags',
            //        'attachments',
            'interest_date',
            'payment_date',
            'invoice_date',
            'book_date',
            'due_date',
            'process_date',
            //        'destination_transaction_id',
            'notes',
        ];
    private readonly ExpressionLanguage $expressionLanguage;
    private readonly bool               $isExpression;
    private readonly ?SyntaxError       $validationError;

    public function __construct(private readonly string $expr)
    {
        $this->expressionLanguage = app(ExpressionLanguage::class);

        $this->isExpression       = self::isExpression($this->expr);
        $this->validationError    = $this->validate();
    }

    private static function isExpression(string $expr): bool
    {
        return str_starts_with($expr, '=') && strlen($expr) > 1;
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

    private function lint(): void
    {
        if (!$this->isExpression) {
            return;
        }

        $this->lintExpression(substr($this->expr, 1));
    }

    private function lintExpression(string $expr): void
    {
        $this->expressionLanguage->lint($expr, self::$NAMES);
    }

    public function getValidationError(): ?SyntaxError
    {
        return $this->validationError;
    }

    public function isValid(): bool
    {
        return !$this->validationError instanceof SyntaxError;
    }

    private function evaluateExpression(string $expr, array $journal): string
    {
        $result = $this->expressionLanguage->evaluate($expr, $journal);

        return (string) $result;
    }

    public function evaluate(array $journal): string
    {
        if (!$this->isExpression) {
            return $this->expr;
        }

        return $this->evaluateExpression(substr($this->expr, 1), $journal);
    }
}
