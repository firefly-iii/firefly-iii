<?php

/**
 * JournalList.php
 * Copyright (c) 2019 james@firefly-iii.org
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

namespace FireflyIII\Support\Binder;

use FireflyIII\Enums\TransactionTypeEnum;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Models\TransactionType;
use Illuminate\Routing\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class JournalList.
 */
class JournalList implements BinderInterface
{
    /**
     * @throws NotFoundHttpException
     */
    public static function routeBinder(string $value, Route $route): array
    {
        if (auth()->check()) {
            $list      = self::parseList($value);

            // get the journals by using the collector.
            /** @var GroupCollectorInterface $collector */
            $collector = app(GroupCollectorInterface::class);
            $collector->setTypes([TransactionTypeEnum::WITHDRAWAL->value, TransactionType::DEPOSIT, TransactionType::TRANSFER, TransactionType::RECONCILIATION]);
            $collector->withCategoryInformation()->withBudgetInformation()->withTagInformation()->withAccountInformation();
            $collector->setJournalIds($list);
            $result    = $collector->getExtractedJournals();
            if (0 === count($result)) {
                throw new NotFoundHttpException();
            }

            return $result;
        }

        throw new NotFoundHttpException();
    }

    protected static function parseList(string $value): array
    {
        $list = array_unique(array_map('\intval', explode(',', $value)));
        if (0 === count($list)) { // @phpstan-ignore-line
            throw new NotFoundHttpException();
        }

        return $list;
    }
}
