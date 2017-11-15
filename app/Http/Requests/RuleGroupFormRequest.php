<?php
/**
 * RuleGroupFormRequest.php
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

namespace FireflyIII\Http\Requests;

use FireflyIII\Repositories\RuleGroup\RuleGroupRepositoryInterface;

/**
 * Class RuleGroupFormRequest.
 */
class RuleGroupFormRequest extends Request
{
    /**
     * @return bool
     */
    public function authorize()
    {
        // Only allow logged in users
        return auth()->check();
    }

    /**
     * @return array
     */
    public function getRuleGroupData(): array
    {
        return [
            'title'       => $this->string('title'),
            'description' => $this->string('description'),
        ];
    }

    /**
     * @return array
     */
    public function rules()
    {
        // fixed
        /** @var RuleGroupRepositoryInterface $repository */
        $repository = app(RuleGroupRepositoryInterface::class);
        $titleRule  = 'required|between:1,100|uniqueObjectForUser:rule_groups,title';
        if (null !== $repository->find(intval($this->get('id')))->id) {
            $titleRule = 'required|between:1,100|uniqueObjectForUser:rule_groups,title,' . intval($this->get('id'));
        }

        return [
            'title'       => $titleRule,
            'description' => 'between:1,5000|nullable',
        ];
    }
}
