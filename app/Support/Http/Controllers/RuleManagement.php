<?php
/**
 * RuleManagement.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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

declare(strict_types=1);

namespace FireflyIII\Support\Http\Controllers;

use FireflyIII\Repositories\Rule\RuleRepositoryInterface;
use FireflyIII\Repositories\RuleGroup\RuleGroupRepositoryInterface;
use Illuminate\Http\Request;
use Log;
use Throwable;

/**
 * Trait RuleManagement
 *
 */
trait RuleManagement
{

    /**
     *
     */
    protected function createDefaultRule(): void
    {
        /** @var RuleRepositoryInterface $ruleRepository */
        $ruleRepository = app(RuleRepositoryInterface::class);
        if (0 === $ruleRepository->count()) {
            $data = [
                'rule_group_id'   => $ruleRepository->getFirstRuleGroup()->id,
                'stop_processing' => 0,
                'title'           => (string)trans('firefly.default_rule_name'),
                'description'     => (string)trans('firefly.default_rule_description'),
                'trigger'         => 'store-journal',
                'strict'          => true,
                'rule_triggers'   => [
                    [
                        'name'            => 'description_is',
                        'value'           => (string)trans('firefly.default_rule_trigger_description'),
                        'stop_processing' => false,

                    ],
                    [
                        'name'            => 'from_account_is',
                        'value'           => (string)trans('firefly.default_rule_trigger_from_account'),
                        'stop_processing' => false,

                    ],

                ],
                'rule_actions'    => [
                    [
                        'name'            => 'prepend_description',
                        'value'           => (string)trans('firefly.default_rule_action_prepend'),
                        'stop_processing' => false,
                    ],
                    [
                        'name'            => 'set_category',
                        'value'           => (string)trans('firefly.default_rule_action_set_category'),
                        'stop_processing' => false,
                    ],
                ],
            ];

            $ruleRepository->store($data);
        }
    }

    /**
     * @param Request $request
     *
     * @return array
     *
     */
    protected function getPreviousActions(Request $request): array
    {
        $index    = 0;
        $triggers = [];
        $oldInput = $request->old('rule_actions');
        if (\is_array($oldInput)) {
            foreach ($oldInput as $oldAction) {
                try {
                    $triggers[] = view(
                        'rules.partials.action',
                        [
                            'oldAction'  => $oldAction['name'],
                            'oldValue'   => $oldAction['value'],
                            'oldChecked' => 1 === (int)($oldAction['stop_processing'] ?? '0'),
                            'count'      => $index + 1,
                        ]
                    )->render();
                } catch (Throwable $e) {
                    Log::debug(sprintf('Throwable was thrown in getPreviousActions(): %s', $e->getMessage()));
                    Log::error($e->getTraceAsString());
                }
                $index++;
            }
        }

        return $triggers;
    }

    /**
     * @param Request $request
     *
     * @return array
     */
    protected function getPreviousTriggers(Request $request): array
    {
        $index    = 0;
        $triggers = [];
        $oldInput = $request->old('rule_triggers');
        if (\is_array($oldInput)) {
            foreach ($oldInput as $oldTrigger) {
                try {
                    $triggers[] = view(
                        'rules.partials.trigger',
                        [
                            'oldTrigger' => $oldTrigger['name'],
                            'oldValue'   => $oldTrigger['value'],
                            'oldChecked' => 1 === (int)($oldTrigger['stop_processing'] ?? '0'),
                            'count'      => $index + 1,
                        ]
                    )->render();
                } catch (Throwable $e) {
                    Log::debug(sprintf('Throwable was thrown in getPreviousTriggers(): %s', $e->getMessage()));
                    Log::error($e->getTraceAsString());
                }
                $index++;
            }
        }

        return $triggers;
    }

    /**
     *
     */
    private function createDefaultRuleGroup(): void
    {
        /** @var RuleGroupRepositoryInterface $repository */
        $repository = app(RuleGroupRepositoryInterface::class);
        if (0 === $repository->count()) {
            $data = [
                'title'       => (string)trans('firefly.default_rule_group_name'),
                'description' => (string)trans('firefly.default_rule_group_description'),
            ];

            $repository->store($data);
        }
    }
}
