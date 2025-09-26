<?php

/**
 * PiggyBankForm.php
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

namespace FireflyIII\Support\Form;

use FireflyIII\Models\PiggyBank;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;

/**
 * Class PiggyBankForm
 *
 * TODO cleanup and describe.
 */
class PiggyBankForm
{
    use FormSupport;

    /**
     * TODO cleanup and describe
     *
     * @param mixed $value
     */
    public function piggyBankList(string $name, $value = null, ?array $options = null): string
    {
        // make repositories
        /** @var PiggyBankRepositoryInterface $repository */
        $repository = app(PiggyBankRepositoryInterface::class);
        $piggyBanks = $repository->getPiggyBanksWithAmount();
        $title      = (string)trans('firefly.default_group_title_name');
        $array      = [];
        $subList    = [
            0 => [
                'group'   => [
                    'title' => $title,
                ],
                'piggies' => [
                    (string)trans('firefly.none_in_select_list'),
                ],
            ],
        ];

        /** @var PiggyBank $piggy */
        foreach ($piggyBanks as $piggy) {
            $group                                       = $piggy->objectGroups->first();
            $groupTitle                                  = null;
            $groupOrder                                  = 0;
            if (null !== $group) {
                $groupTitle = $group->title;
                $groupOrder = $group->order;
            }
            $subList[$groupOrder] ??= [
                'group'   => [
                    'title' => $groupTitle,
                ],
                'piggies' => [],
            ];
            $subList[$groupOrder]['piggies'][$piggy->id] = $piggy->name;
        }
        ksort($subList);
        foreach ($subList as $info) {
            $groupTitle         = $info['group']['title'];
            $array[$groupTitle] = $info['piggies'];
        }

        return $this->select($name, $array, $value, $options);
    }
}
