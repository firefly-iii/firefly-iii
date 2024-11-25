<?php

/**
 * RuleForm.php
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

use FireflyIII\Models\RuleGroup;
use FireflyIII\Repositories\RuleGroup\RuleGroupRepositoryInterface;

/**
 * Class RuleForm
 * TODO cleanup and describe
 */
class RuleForm
{
    use FormSupport;

    public function ruleGroupList(string $name, mixed $value = null, ?array $options = null): string
    {
        /** @var RuleGroupRepositoryInterface $groupRepos */
        $groupRepos = app(RuleGroupRepositoryInterface::class);

        // get all currencies:
        $list       = $groupRepos->get();
        $array      = [];

        /** @var RuleGroup $group */
        foreach ($list as $group) {
            $array[$group->id] = $group->title;
        }

        return $this->select($name, $array, $value, $options);
    }

    /**
     * @param null $value
     */
    public function ruleGroupListWithEmpty(string $name, $value = null, ?array $options = null): string
    {
        $options ??= [];
        $options['class'] = 'form-control';

        /** @var RuleGroupRepositoryInterface $groupRepos */
        $groupRepos       = app(RuleGroupRepositoryInterface::class);

        // get all currencies:
        $list             = $groupRepos->get();
        $array            = [
            0 => (string)trans('firefly.none_in_select_list'),
        ];

        /** @var RuleGroup $group */
        foreach ($list as $group) {
            if (array_key_exists('hidden', $options) && (int)$options['hidden'] !== $group->id) {
                $array[$group->id] = $group->title;
            }
        }

        return $this->select($name, $array, $value, $options);
    }
}
