<?php

/**
 * RuleRepository.php
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

namespace FireflyIII\Repositories\Rule;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Rule;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\RuleGroup;
use FireflyIII\Models\RuleTrigger;
use FireflyIII\Repositories\RuleGroup\RuleGroupRepositoryInterface;
use FireflyIII\Support\Search\OperatorQuerySearch;
use FireflyIII\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;

/**
 * Class RuleRepository.
 */
class RuleRepository implements RuleRepositoryInterface
{
    /** @var User */
    private $user;

    /**
     * @throws \Exception
     */
    public function destroy(Rule $rule): bool
    {
        foreach ($rule->ruleTriggers as $trigger) {
            $trigger->delete();
        }
        foreach ($rule->ruleActions as $action) {
            $action->delete();
        }
        $rule->delete();

        return true;
    }

    public function duplicate(Rule $rule): Rule
    {
        $newRule        = $rule->replicate();
        $newRule->title = (string)trans('firefly.rule_copy_of', ['title' => $rule->title]);
        $newRule->save();

        // replicate all triggers
        /** @var RuleTrigger $trigger */
        foreach ($rule->ruleTriggers as $trigger) {
            $newTrigger          = $trigger->replicate();
            $newTrigger->rule_id = $newRule->id;
            $newTrigger->save();
        }

        // replicate all actions
        /** @var RuleAction $action */
        foreach ($rule->ruleActions as $action) {
            $newAction          = $action->replicate();
            $newAction->rule_id = $newRule->id;
            $newAction->save();
        }

        return $newRule;
    }

    /**
     * Get all the users rules.
     */
    public function getAll(): Collection
    {
        return $this->user->rules()->with(['ruleGroup'])->get();
    }

    /**
     * FIxXME can return null.
     */
    public function getFirstRuleGroup(): RuleGroup
    {
        return $this->user->ruleGroups()->first();
    }

    public function getHighestOrderInRuleGroup(RuleGroup $ruleGroup): int
    {
        return (int)$ruleGroup->rules()->max('order');
    }

    /**
     * @throws FireflyException
     */
    public function getPrimaryTrigger(Rule $rule): string
    {
        $count = $rule->ruleTriggers()->count();
        if (0 === $count) {
            throw new FireflyException('Rules should have more than zero triggers, rule #'.$rule->id.' has none!');
        }

        return $rule->ruleTriggers()->where('trigger_type', 'user_action')->first()->trigger_value;
    }

    public function count(): int
    {
        return $this->user->rules()->count();
    }

    public function getRuleActions(Rule $rule): Collection
    {
        return $rule->ruleActions()->orderBy('order', 'ASC')->get();
    }

    public function getRuleTriggers(Rule $rule): Collection
    {
        return $rule->ruleTriggers()->orderBy('order', 'ASC')->get();
    }

    public function getSearchQuery(Rule $rule): string
    {
        $params = [];

        /** @var RuleTrigger $trigger */
        foreach ($rule->ruleTriggers as $trigger) {
            if ('user_action' === $trigger->trigger_type) {
                continue;
            }
            $triggerType  = $trigger->trigger_type;
            if (str_starts_with($trigger->trigger_type, '-')) {
                $triggerType = substr($trigger->trigger_type, 1);
            }
            $needsContext = config(sprintf('search.operators.%s.needs_context', $triggerType)) ?? true;
            if (false === $needsContext) {
                $params[] = sprintf('%s:true', OperatorQuerySearch::getRootOperator($trigger->trigger_type));
            }
            if (true === $needsContext) {
                $params[] = sprintf('%s:"%s"', OperatorQuerySearch::getRootOperator($trigger->trigger_type), $trigger->trigger_value);
            }
        }

        return implode(' ', $params);
    }

    public function getStoreRules(): Collection
    {
        $collection = $this->user->rules()
            ->leftJoin('rule_groups', 'rule_groups.id', '=', 'rules.rule_group_id')
            ->where('rules.active', true)
            ->where('rule_groups.active', true)
            ->orderBy('rule_groups.order', 'ASC')
            ->orderBy('rules.order', 'ASC')
            ->orderBy('rules.id', 'ASC')
            ->with(['ruleGroup', 'ruleTriggers'])->get(['rules.*'])
        ;
        $filtered   = new Collection();

        /** @var Rule $rule */
        foreach ($collection as $rule) {
            /** @var RuleTrigger $ruleTrigger */
            foreach ($rule->ruleTriggers as $ruleTrigger) {
                if ('user_action' === $ruleTrigger->trigger_type && 'store-journal' === $ruleTrigger->trigger_value) {
                    $filtered->push($rule);
                }
            }
        }

        return $filtered;
    }

    public function getUpdateRules(): Collection
    {
        $collection = $this->user->rules()
            ->leftJoin('rule_groups', 'rule_groups.id', '=', 'rules.rule_group_id')
            ->where('rules.active', true)
            ->where('rule_groups.active', true)
            ->orderBy('rule_groups.order', 'ASC')
            ->orderBy('rules.order', 'ASC')
            ->orderBy('rules.id', 'ASC')
            ->with(['ruleGroup', 'ruleTriggers'])->get()
        ;
        $filtered   = new Collection();

        /** @var Rule $rule */
        foreach ($collection as $rule) {
            /** @var RuleTrigger $ruleTrigger */
            foreach ($rule->ruleTriggers as $ruleTrigger) {
                if ('user_action' === $ruleTrigger->trigger_type && 'update-journal' === $ruleTrigger->trigger_value) {
                    $filtered->push($rule);
                }
            }
        }

        return $filtered;
    }

    public function searchRule(string $query, int $limit): Collection
    {
        $search = $this->user->rules();
        if ('' !== $query) {
            $search->whereLike('rules.title', sprintf('%%%s%%', $query));
        }
        $search->orderBy('rules.order', 'ASC')
            ->orderBy('rules.title', 'ASC')
        ;

        return $search->take($limit)->get(['id', 'title', 'description']);
    }

    /**
     * @throws FireflyException
     */
    public function store(array $data): Rule
    {
        $ruleGroup             = null;
        if (array_key_exists('rule_group_id', $data)) {
            $ruleGroup = $this->user->ruleGroups()->find($data['rule_group_id']);
        }
        if (array_key_exists('rule_group_title', $data)) {
            $ruleGroup = $this->user->ruleGroups()->where('title', $data['rule_group_title'])->first();
        }
        if (null === $ruleGroup) {
            throw new FireflyException('No such rule group.');
        }

        // start by creating a new rule:
        $rule                  = new Rule();
        $rule->user()->associate($this->user);
        $rule->userGroup()->associate($this->user->userGroup);

        $rule->rule_group_id   = $ruleGroup->id;
        $rule->order           = 31337;
        $rule->active          = array_key_exists('active', $data) ? $data['active'] : true;
        $rule->strict          = array_key_exists('strict', $data) ? $data['strict'] : false;
        $rule->stop_processing = array_key_exists('stop_processing', $data) ? $data['stop_processing'] : false;
        $rule->title           = array_key_exists('title', $data) ? $data['title'] : '';
        $rule->description     = array_key_exists('description', $data) ? $data['description'] : '';
        $rule->save();
        $rule->refresh();

        // save update trigger:
        $this->setRuleTrigger($data['trigger'] ?? 'store-journal', $rule);

        // reset order:
        $this->resetRuleOrder($ruleGroup);
        app('log')->debug('Done with resetting.');
        if (array_key_exists('order', $data)) {
            app('log')->debug(sprintf('User has submitted order %d', $data['order']));
            $this->setOrder($rule, $data['order']);
        }

        // start storing triggers:
        $this->storeTriggers($rule, $data);

        // same for actions.
        $this->storeActions($rule, $data);
        $rule->refresh();

        return $rule;
    }

    public function find(int $ruleId): ?Rule
    {
        return $this->user->rules()->find($ruleId);
    }

    private function setRuleTrigger(string $moment, Rule $rule): void
    {
        /** @var null|RuleTrigger $trigger */
        $trigger                  = $rule->ruleTriggers()->where('trigger_type', 'user_action')->first();
        if (null !== $trigger) {
            $trigger->trigger_value = $moment;
            $trigger->save();

            return;
        }
        $trigger                  = new RuleTrigger();
        $trigger->order           = 0;
        $trigger->trigger_type    = 'user_action';
        $trigger->trigger_value   = $moment;
        $trigger->rule_id         = $rule->id;
        $trigger->active          = true;
        $trigger->stop_processing = false;
        $trigger->save();
    }

    public function resetRuleOrder(RuleGroup $ruleGroup): bool
    {
        $groupRepository = app(RuleGroupRepositoryInterface::class);
        $groupRepository->setUser($ruleGroup->user);
        $groupRepository->resetRuleOrder($ruleGroup);

        return true;
    }

    public function setUser(null|Authenticatable|User $user): void
    {
        if ($user instanceof User) {
            $this->user = $user;
        }
    }

    public function setOrder(Rule $rule, int $newOrder): void
    {
        $oldOrder    = $rule->order;
        $groupId     = $rule->rule_group_id;
        $maxOrder    = $this->maxOrder($rule->ruleGroup);
        $newOrder    = $newOrder > $maxOrder ? $maxOrder + 1 : $newOrder;
        app('log')->debug(sprintf('New order will be %d', $newOrder));

        if ($newOrder > $oldOrder) {
            $this->user->rules()
                ->where('rules.rule_group_id', $groupId)
                ->where('rules.order', '<=', $newOrder)
                ->where('rules.order', '>', $oldOrder)
                ->where('rules.id', '!=', $rule->id)
                ->decrement('rules.order')
            ;
            $rule->order = $newOrder;
            app('log')->debug(sprintf('Order of rule #%d ("%s") is now %d', $rule->id, $rule->title, $newOrder));
            $rule->save();

            return;
        }

        $this->user->rules()
            ->where('rules.rule_group_id', $groupId)
            ->where('rules.order', '>=', $newOrder)
            ->where('rules.order', '<', $oldOrder)
            ->where('rules.id', '!=', $rule->id)
            ->increment('rules.order')
        ;
        $rule->order = $newOrder;
        app('log')->debug(sprintf('Order of rule #%d ("%s") is now %d', $rule->id, $rule->title, $newOrder));
        $rule->save();
    }

    public function maxOrder(RuleGroup $ruleGroup): int
    {
        return (int)$ruleGroup->rules()->max('order');
    }

    private function storeTriggers(Rule $rule, array $data): void
    {
        $order = 1;
        foreach ($data['triggers'] as $trigger) {
            $value          = $trigger['value'] ?? '';
            $stopProcessing = $trigger['stop_processing'] ?? false;
            $active         = $trigger['active'] ?? true;
            $type           = $trigger['type'];
            if (true === ($trigger['prohibited'] ?? false) && !str_starts_with($type, '-')) {
                $type = sprintf('-%s', $type);
            }

            // empty the value in case the rule needs no context
            // TODO create a helper to automatically return these.
            $needTrue       = [
                'reconciled',
                'has_attachments',
                'has_any_category',
                'has_any_budget',
                'has_any_bill',
                'has_any_tag',
                'any_notes',
                'any_external_url',
                'has_no_attachments',
                'has_no_category',
                'has_no_budget',
                'has_no_bill',
                'has_no_tag',
                'no_notes',
                'no_external_url',
                'source_is_cash',
                'destination_is_cash',
                'account_is_cash',
                'exists',
                'no_external_id',
                'any_external_id',
            ];
            if (in_array($type, $needTrue, true)) {
                $value = '';
            }

            $triggerValues  = [
                'action'          => $type,
                'value'           => $value,
                'stop_processing' => $stopProcessing,
                'order'           => $order,
                'active'          => $active,
            ];
            $this->storeTrigger($rule, $triggerValues);
            ++$order;
        }
    }

    public function storeTrigger(Rule $rule, array $values): RuleTrigger
    {
        $ruleTrigger                  = new RuleTrigger();
        $ruleTrigger->rule()->associate($rule);
        $ruleTrigger->order           = $values['order'];
        $ruleTrigger->active          = $values['active'];
        $ruleTrigger->stop_processing = $values['stop_processing'];
        $ruleTrigger->trigger_type    = $values['action'];
        $ruleTrigger->trigger_value   = $values['value'] ?? '';
        $ruleTrigger->save();

        return $ruleTrigger;
    }

    private function storeActions(Rule $rule, array $data): void
    {
        $order = 1;
        foreach ($data['actions'] as $action) {
            $value          = $action['value'] ?? '';
            $stopProcessing = $action['stop_processing'] ?? false;
            $active         = $action['active'] ?? true;
            $actionValues   = [
                'action'          => $action['type'],
                'value'           => $value,
                'stop_processing' => $stopProcessing,
                'order'           => $order,
                'active'          => $active,
            ];
            $this->storeAction($rule, $actionValues);
            ++$order;
        }
    }

    public function storeAction(Rule $rule, array $values): RuleAction
    {
        $ruleAction                  = new RuleAction();
        $ruleAction->rule()->associate($rule);
        $ruleAction->order           = $values['order'];
        $ruleAction->active          = $values['active'];
        $ruleAction->stop_processing = $values['stop_processing'];
        $ruleAction->action_type     = $values['action'];
        $ruleAction->action_value    = $values['value'] ?? '';
        $ruleAction->save();

        return $ruleAction;
    }

    public function update(Rule $rule, array $data): Rule
    {
        // update rule:
        $fields = [
            'title',
            'description',
            'strict',
            'rule_group_id',
            'active',
            'stop_processing',
        ];
        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $rule->{$field} = $data[$field];
            }
        }
        $rule->save();
        $rule->refresh();
        $group  = $rule->ruleGroup;
        // update the order:
        $this->resetRuleOrder($group);
        if (array_key_exists('order', $data)) {
            $this->moveRule($rule, $group, (int)$data['order']);
        }

        // update the triggers:
        if (array_key_exists('trigger', $data) && 'update-journal' === $data['trigger']) {
            $this->setRuleTrigger('update-journal', $rule);
        }
        if (array_key_exists('trigger', $data) && 'store-journal' === $data['trigger']) {
            $this->setRuleTrigger('store-journal', $rule);
        }
        if (array_key_exists('triggers', $data)) {
            // delete triggers:
            $rule->ruleTriggers()->where('trigger_type', '!=', 'user_action')->delete();

            // recreate triggers:
            $this->storeTriggers($rule, $data);
        }

        if (array_key_exists('actions', $data)) {
            // delete triggers:
            $rule->ruleActions()->delete();

            // recreate actions:
            $this->storeActions($rule, $data);
        }

        return $rule;
    }

    public function moveRule(Rule $rule, RuleGroup $ruleGroup, int $order): Rule
    {
        if ($rule->rule_group_id !== $ruleGroup->id) {
            $rule->rule_group_id = $ruleGroup->id;
        }
        $rule->save();
        $rule->refresh();
        $this->setOrder($rule, $order);

        return $rule;
    }
}
