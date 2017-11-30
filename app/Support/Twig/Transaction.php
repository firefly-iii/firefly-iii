<?php
/**
 * Transaction.php
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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Support\Twig;

use FireflyIII\Support\Twig\Extension\Transaction as TransactionExtension;
use Twig_Extension;
use Twig_SimpleFilter;

/**
 * Class Transaction.
 */
class Transaction extends Twig_Extension
{
    /**
     * @return array
     */
    public function getFilters(): array
    {
        $filters = [
            new Twig_SimpleFilter('transactionIcon', [TransactionExtension::class, 'icon'], ['is_safe' => ['html']]),
            new Twig_SimpleFilter('transactionDescription', [TransactionExtension::class, 'description']),
            new Twig_SimpleFilter('transactionIsSplit', [TransactionExtension::class, 'isSplit'], ['is_safe' => ['html']]),
            new Twig_SimpleFilter('transactionReconciled', [TransactionExtension::class, 'isReconciled'], ['is_safe' => ['html']]),
            new Twig_SimpleFilter('transactionHasAtt', [TransactionExtension::class, 'hasAttachments'], ['is_safe' => ['html']]),
            new Twig_SimpleFilter('transactionAmount', [TransactionExtension::class, 'amount'], ['is_safe' => ['html']]),
            new Twig_SimpleFilter('transactionArrayAmount', [TransactionExtension::class, 'amountArray'], ['is_safe' => ['html']]),
            new Twig_SimpleFilter('transactionBudgets', [TransactionExtension::class, 'budgets'], ['is_safe' => ['html']]),
            new Twig_SimpleFilter('transactionCategories', [TransactionExtension::class, 'categories'], ['is_safe' => ['html']]),
            new Twig_SimpleFilter('transactionSourceAccount', [TransactionExtension::class, 'sourceAccount'], ['is_safe' => ['html']]),
            new Twig_SimpleFilter('transactionDestinationAccount', [TransactionExtension::class, 'destinationAccount'], ['is_safe' => ['html']]),
        ];

        return $filters;
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName(): string
    {
        return 'transaction';
    }
}
