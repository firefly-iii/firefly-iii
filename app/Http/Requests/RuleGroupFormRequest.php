<?php
/**
 * RuleGroupFormRequest.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Http\Requests;

use FireflyIII\Repositories\RuleGroup\RuleGroupRepositoryInterface;

/**
 * Class RuleGroupFormRequest
 *
 *
 * @package FireflyIII\Http\Requests
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
        /** @var RuleGroupRepositoryInterface $repository */
        $repository = app(RuleGroupRepositoryInterface::class);
        $titleRule  = 'required|between:1,100|uniqueObjectForUser:rule_groups,title';
        if (!is_null($repository->find(intval($this->get('id')))->id)) {
            $titleRule = 'required|between:1,100|uniqueObjectForUser:rule_groups,title,' . intval($this->get('id'));
        }

        return [
            'title'       => $titleRule,
            'description' => 'between:1,5000|nullable',
        ];
    }
}
