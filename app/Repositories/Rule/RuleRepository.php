<?php
/**
 * RuleRepository.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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
use FireflyIII\User;
use Illuminate\Support\Collection;
use Log;

/**
 * Class RuleRepository.
 *
 */
class RuleRepository implements RuleRepositoryInterface
{
    /** @var User */
    private $user;

    /**
     * Constructor.
     */
    public function __construct()
    {
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', get_class($this)));
        }
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return $this->user->rules()->count();
    }

    /**
     * @param Rule $rule
     *
     * @return bool
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

    /**
     * @param int $ruleId
     *
     * @return Rule|null
     */
    public function find(int $ruleId): ?Rule
    {
        $rule = $this->user->rules()->find($ruleId);
        if (null === $rule) {
            return null;
        }

        return $rule;
    }

    /**
     * Get all the users rules.
     *
     * @return Collection
     */
    public function getAll(): Collection
    {
        return $this->user->rules()->with(['ruleGroup'])->get();
    }

    /**
     * FIxXME can return null.
     *
     * @return RuleGroup
     */
    public function getFirstRuleGroup(): RuleGroup
    {
        return $this->user->ruleGroups()->first();
    }

    /**
     * Get the rules for a user tailored to the import process.
     *
     * @return Collection
     */
    public function getForImport(): Collection
    {
        return Rule::distinct()
                   ->where('rules.user_id', $this->user->id)
                   ->leftJoin('rule_groups', 'rule_groups.id', '=', 'rules.rule_group_id')
                   ->leftJoin('rule_triggers', 'rules.id', '=', 'rule_triggers.rule_id')
                   ->where('rule_groups.active', 1)
                   ->where('rule_triggers.trigger_type', 'user_action')
                   ->where('rule_triggers.trigger_value', 'store-journal')
                   ->where('rules.active', 1)
                   ->orderBy('rule_groups.order', 'ASC')
                   ->orderBy('rules.order', 'ASC')
                   ->get(['rules.*', 'rule_groups.order']);
    }

    /**
     * @param RuleGroup $ruleGroup
     *
     * @return int
     */
    public function getHighestOrderInRuleGroup(RuleGroup $ruleGroup): int
    {
        return (int)$ruleGroup->rules()->max('order');
    }

    /**
     * @param Rule $rule
     *
     * @return string
     *
     * @throws FireflyException
     */
    public function getPrimaryTrigger(Rule $rule): string
    {
        $count = $rule->ruleTriggers()->count();
        if (0 === $count) {
            throw new FireflyException('Rules should have more than zero triggers, rule #' . $rule->id . ' has none!');
        }

        return $rule->ruleTriggers()->where('trigger_type', 'user_action')->first()->trigger_value;
    }

    /**
     * @param Rule $rule
     *
     * @return Collection
     */
    public function getRuleActions(Rule $rule): Collection
    {
        return $rule->ruleActions()->orderBy('order', 'ASC')->get();
    }

    /**
     * @param Rule $rule
     *
     * @return Collection
     */
    public function getRuleTriggers(Rule $rule): Collection
    {
        return $rule->ruleTriggers()->orderBy('order', 'ASC')->get();
    }

    /**
     * @param Rule $rule
     *
     * @return bool
     */
    public function moveDown(Rule $rule): bool
    {
        $order = $rule->order;

        // find the rule with order+1 and give it order-1
        $other = $rule->ruleGroup->rules()->where('order', $order + 1)->first();
        if ($other) {
            --$other->order;
            $other->save();
        }

        ++$rule->order;
        $rule->save();
        $this->resetRulesInGroupOrder($rule->ruleGroup);

        return true;
    }

    /**
     * @param Rule $rule
     *
     * @return bool
     */
    public function moveUp(Rule $rule): bool
    {
        $order = $rule->order;

        // find the rule with order-1 and give it order+1
        $other = $rule->ruleGroup->rules()->where('order', $order - 1)->first();
        if ($other) {
            ++$other->order;
            $other->save();
        }

        --$rule->order;
        $rule->save();
        $this->resetRulesInGroupOrder($rule->ruleGroup);

        return true;
    }

    /**
     * @param Rule  $rule
     * @param array $ids
     *
     * @return bool
     */
    public function reorderRuleActions(Rule $rule, array $ids): bool
    {
        $order = 1;
        foreach ($ids as $actionId) {
            /** @var RuleTrigger $trigger */
            $action = $rule->ruleActions()->find($actionId);
            if (null !== $action) {
                $action->order = $order;
                $action->save();
                ++$order;
            }
        }

        return true;
    }

    /**
     * @param Rule  $rule
     * @param array $ids
     *
     * @return bool
     */
    public function reorderRuleTriggers(Rule $rule, array $ids): bool
    {
        $order = 1;
        foreach ($ids as $triggerId) {
            /** @var RuleTrigger $trigger */
            $trigger = $rule->ruleTriggers()->find($triggerId);
            if (null !== $trigger) {
                $trigger->order = $order;
                $trigger->save();
                ++$order;
            }
        }

        return true;
    }

    /**
     * @param RuleGroup $ruleGroup
     *
     * @return bool
     */
    public function resetRulesInGroupOrder(RuleGroup $ruleGroup): bool
    {
        $ruleGroup->rules()->withTrashed()->whereNotNull('deleted_at')->update(['order' => 0]);

        $set   = $ruleGroup->rules()
                           ->orderBy('order', 'ASC')
                           ->orderBy('updated_at', 'DESC')
                           ->get();
        $count = 1;
        /** @var Rule $entry */
        foreach ($set as $entry) {
            $entry->order = $count;
            $entry->save();
            ++$count;
        }

        return true;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    /**
     * @param array $data
     *
     * @return Rule
     */
    public function store(array $data): Rule
    {
        /** @var RuleGroup $ruleGroup */
        $ruleGroup = $this->user->ruleGroups()->find($data['rule_group_id']);

        // get max order:
        $order = $this->getHighestOrderInRuleGroup($ruleGroup);

        // start by creating a new rule:
        $rule = new Rule;
        $rule->user()->associate($this->user->id);

        $rule->rule_group_id   = $data['rule_group_id'];
        $rule->order           = ($order + 1);
        $rule->active          = $data['active'];
        $rule->strict          = $data['strict'];
        $rule->stop_processing = $data['stop_processing'];
        $rule->title           = $data['title'];
        $rule->description     = strlen($data['description']) > 0 ? $data['description'] : null;

        $rule->save();

        // start storing triggers:
        $this->storeTriggers($rule, $data);

        // same for actions.
        $this->storeActions($rule, $data);

        return $rule;
    }

    /**
     * @param Rule  $rule
     * @param array $values
     *
     * @return RuleAction
     */
    public function storeAction(Rule $rule, array $values): RuleAction
    {
        $ruleAction = new RuleAction;
        $ruleAction->rule()->associate($rule);
        $ruleAction->order           = $values['order'];
        $ruleAction->active          = true;
        $ruleAction->stop_processing = $values['stop_processing'];
        $ruleAction->action_type     = $values['action'];
        $ruleAction->action_value    = $values['value'] ?? '';
        $ruleAction->save();

        return $ruleAction;
    }

    /**
     * @param Rule  $rule
     * @param array $values
     *
     * @return RuleTrigger
     */
    public function storeTrigger(Rule $rule, array $values): RuleTrigger
    {
        $ruleTrigger = new RuleTrigger;
        $ruleTrigger->rule()->associate($rule);
        $ruleTrigger->order           = $values['order'];
        $ruleTrigger->active          = true;
        $ruleTrigger->stop_processing = $values['stop_processing'];
        $ruleTrigger->trigger_type    = $values['action'];
        $ruleTrigger->trigger_value   = $values['value'] ?? '';
        $ruleTrigger->save();

        return $ruleTrigger;
    }

    /**
     * @param Rule  $rule
     * @param array $data
     *
     * @return Rule
     */
    public function update(Rule $rule, array $data): Rule
    {
        // update rule:

        $rule->rule_group_id   = $data['rule_group_id'] ?? $rule->rule_group_id;
        $rule->active          = $data['active'] ?? $rule->active;
        $rule->stop_processing = $data['stop_processing'] ?? $rule->stop_processing;
        $rule->title           = $data['title'] ?? $rule->title;
        $rule->strict          = $data['strict'] ?? $rule->strict;
        $rule->description     = $data['description'] ?? $rule->description;
        $rule->save();

        if (null !== $data['triggers']) {
            // delete triggers:
            $rule->ruleTriggers()->delete();

            // recreate triggers:
            $this->storeTriggers($rule, $data);
        }
        if (null !== $data['actions']) {
            // delete actions:
            $rule->ruleActions()->delete();

            // recreate actions:
            $this->storeActions($rule, $data);
        }

        return $rule;
    }

    /**
     * @param Rule  $rule
     * @param array $data
     *
     * @return bool
     */
    private function storeActions(Rule $rule, array $data): bool
    {
        $order = 1;
        foreach ($data['actions'] as $action) {
            $value          = $action['value'] ?? '';
            $stopProcessing = $action['stop_processing'] ?? false;

            $actionValues = [
                'action'          => $action['type'],
                'value'           => $value,
                'stop_processing' => $stopProcessing,
                'order'           => $order,
            ];

            $this->storeAction($rule, $actionValues);
        }

        return true;
    }

    /**
     * @param Rule  $rule
     * @param array $data
     *
     * @return bool
     */
    private function storeTriggers(Rule $rule, array $data): bool
    {
        $order          = 1;
        $stopProcessing = false;

        $triggerValues = [
            'action'          => 'user_action',
            'value'           => $data['trigger'],
            'stop_processing' => $stopProcessing,
            'order'           => $order,
        ];

        $this->storeTrigger($rule, $triggerValues);
        foreach ($data['triggers'] as $trigger) {
            $value          = $trigger['value'] ?? '';
            $stopProcessing = $trigger['stop_processing'] ?? false;

            $triggerValues = [
                'action'          => $trigger['type'],
                'value'           => $value,
                'stop_processing' => $stopProcessing,
                'order'           => $order,
            ];

            $this->storeTrigger($rule, $triggerValues);
            ++$order;
        }

        return true;
    }
}
