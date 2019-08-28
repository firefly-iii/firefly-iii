<?php
declare(strict_types=1);
/**
 * RuleForm.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

namespace FireflyIII\Support\Form;


use FireflyIII\Models\RuleGroup;
use FireflyIII\Repositories\RuleGroup\RuleGroupRepositoryInterface;
use Form;
use Illuminate\Support\HtmlString;

/**
 * Class RuleForm
 * TODO cleanup and describe.
 */
class RuleForm
{
    use FormSupport;
    /**
     * @param string $name
     * @param mixed $value
     * @param array $options
     *
     * @return string
     */
    public function ruleGroupList(string $name, $value = null, array $options = null): string
    {
        /** @var RuleGroupRepositoryInterface $groupRepos */
        $groupRepos = app(RuleGroupRepositoryInterface::class);

        // get all currencies:
        $list  = $groupRepos->get();
        $array = [];
        /** @var RuleGroup $group */
        foreach ($list as $group) {
            $array[$group->id] = $group->title;
        }

        return $this->select($name, $array, $value, $options);
    }

    /**
     * @param string $name
     * @param null $value
     * @param array|null $options
     *
     * @return HtmlString
     */
    public function ruleGroupListWithEmpty(string $name, $value = null, array $options = null): HtmlString
    {
        $options          = $options ?? [];
        $options['class'] = 'form-control';
        /** @var RuleGroupRepositoryInterface $groupRepos */
        $groupRepos = app(RuleGroupRepositoryInterface::class);

        // get all currencies:
        $list  = $groupRepos->get();
        $array = [
            0 => (string)trans('firefly.none_in_select_list'),
        ];
        /** @var RuleGroup $group */
        foreach ($list as $group) {
            if (isset($options['hidden']) && (int)$options['hidden'] !== $group->id) {
                $array[$group->id] = $group->title;
            }
        }

        return Form::select($name, $array, $value, $options);
    }
}
