<?php

/**
 * RuleGroupRepository.php
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

namespace FireflyIII\Repositories\RuleGroup;

use FireflyIII\Models\Rule;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\RuleGroup;
use FireflyIII\Models\RuleTrigger;
use FireflyIII\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Class RuleGroupRepository.
 */
class RuleGroupRepository implements RuleGroupRepositoryInterface
{
    private User $user;

    public function correctRuleGroupOrder(): void
    {
        $set   = $this->user
            ->ruleGroups()
            ->orderBy('order', 'ASC')
            ->orderBy('active', 'DESC')
            ->orderBy('title', 'ASC')
            ->get(['rule_groups.id'])
        ;
        $index = 1;

        /** @var RuleGroup $ruleGroup */
        foreach ($set as $ruleGroup) {
            if ($ruleGroup->order !== $index) {
                $ruleGroup->order = $index;
                $ruleGroup->save();
            }
            ++$index;
        }
    }

    public function get(): Collection
    {
        return $this->user->ruleGroups()->orderBy('order', 'ASC')->get();
    }

    public function count(): int
    {
        return $this->user->ruleGroups()->count();
    }

    /**
     * @throws \Exception
     */
    public function destroy(RuleGroup $ruleGroup, ?RuleGroup $moveTo): bool
    {
        /** @var Rule $rule */
        foreach ($ruleGroup->rules as $rule) {
            if (null === $moveTo) {
                $rule->delete();

                continue;
            }
            // move
            $rule->ruleGroup()->associate($moveTo);
            $rule->save();
        }

        $ruleGroup->delete();

        $this->resetOrder();
        if (null !== $moveTo) {
            $this->resetRuleOrder($moveTo);
        }

        return true;
    }

    public function resetOrder(): bool
    {
        $set   = $this->user
            ->ruleGroups()
            ->whereNull('deleted_at')
            ->orderBy('order', 'ASC')
            ->orderBy('title', 'DESC')
            ->get()
        ;
        $count = 1;

        /** @var RuleGroup $entry */
        foreach ($set as $entry) {
            if ($entry->order !== $count) {
                $entry->order = $count;
                $entry->save();
            }

            // also update rules in group.
            $this->resetRuleOrder($entry);

            ++$count;
        }

        return true;
    }

    public function resetRuleOrder(RuleGroup $ruleGroup): bool
    {
        $set   = $ruleGroup->rules()
            ->orderBy('order', 'ASC')
            ->orderBy('title', 'DESC')
            ->orderBy('updated_at', 'DESC')
            ->get(['rules.*'])
        ;
        $count = 1;

        /** @var Rule $entry */
        foreach ($set as $entry) {
            if ($entry->order !== $count) {
                app('log')->debug(sprintf('Rule #%d was on spot %d but must be on spot %d', $entry->id, $entry->order, $count));
                $entry->order = $count;
                $entry->save();
            }
            $this->resetRuleActionOrder($entry);
            $this->resetRuleTriggerOrder($entry);

            ++$count;
        }

        return true;
    }

    private function resetRuleActionOrder(Rule $rule): void
    {
        $actions = $rule->ruleActions()
            ->orderBy('order', 'ASC')
            ->orderBy('active', 'DESC')
            ->orderBy('action_type', 'ASC')
            ->get()
        ;
        $index   = 1;

        /** @var RuleAction $action */
        foreach ($actions as $action) {
            if ($action->order !== $index) {
                $action->order = $index;
                $action->save();
                app('log')->debug(sprintf('Rule action #%d was on spot %d but must be on spot %d', $action->id, $action->order, $index));
            }
            ++$index;
        }
    }

    private function resetRuleTriggerOrder(Rule $rule): void
    {
        $triggers = $rule->ruleTriggers()
            ->orderBy('order', 'ASC')
            ->orderBy('active', 'DESC')
            ->orderBy('trigger_type', 'ASC')
            ->get()
        ;
        $index    = 1;

        /** @var RuleTrigger $trigger */
        foreach ($triggers as $trigger) {
            $order = $trigger->order;
            if ($order !== $index) {
                $trigger->order = $index;
                $trigger->save();
                app('log')->debug(sprintf('Rule trigger #%d was on spot %d but must be on spot %d', $trigger->id, $order, $index));
            }
            ++$index;
        }
    }

    public function destroyAll(): void
    {
        Log::channel('audit')->info('Delete all rule groups through destroyAll');
        $groups = $this->get();

        /** @var RuleGroup $group */
        foreach ($groups as $group) {
            $group->rules()->delete();
            $group->delete();
        }
    }

    public function find(int $ruleGroupId): ?RuleGroup
    {
        /** @var null|RuleGroup */
        return $this->user->ruleGroups()->find($ruleGroupId);
    }

    public function findByTitle(string $title): ?RuleGroup
    {
        /** @var null|RuleGroup */
        return $this->user->ruleGroups()->where('title', $title)->first();
    }

    public function getActiveGroups(): Collection
    {
        return $this->user->ruleGroups()->with(['rules'])->where('rule_groups.active', true)->orderBy('order', 'ASC')->get(['rule_groups.*']);
    }

    public function getActiveRules(RuleGroup $group): Collection
    {
        return $group->rules()
            ->where('rules.active', true)
            ->get(['rules.*'])
        ;
    }

    public function getActiveStoreRules(RuleGroup $group): Collection
    {
        return $group->rules()
            ->leftJoin('rule_triggers', 'rules.id', '=', 'rule_triggers.rule_id')
            ->where('rule_triggers.trigger_type', 'user_action')
            ->where('rule_triggers.trigger_value', 'store-journal')
            ->where('rules.active', true)
            ->get(['rules.*'])
        ;
    }

    public function getActiveUpdateRules(RuleGroup $group): Collection
    {
        return $group->rules()
            ->leftJoin('rule_triggers', 'rules.id', '=', 'rule_triggers.rule_id')
            ->where('rule_triggers.trigger_type', 'user_action')
            ->where('rule_triggers.trigger_value', 'update-journal')
            ->where('rules.active', true)
            ->get(['rules.*'])
        ;
    }

    public function getAllRuleGroupsWithRules(?string $filter): Collection
    {
        $groups = $this->user->ruleGroups()
            ->orderBy('order', 'ASC')
            ->with(
                [
                    'rules'              => static function (HasMany $query): void {
                        $query->orderBy('order', 'ASC');
                    },
                    'rules.ruleTriggers' => static function (HasMany $query): void {
                        $query->orderBy('order', 'ASC');
                    },
                    'rules.ruleActions'  => static function (HasMany $query): void {
                        $query->orderBy('order', 'ASC');
                    },
                ]
            )->get()
        ;
        if (null === $filter) {
            return $groups;
        }
        app('log')->debug(sprintf('Will filter getRuleGroupsWithRules on "%s".', $filter));

        return $groups->map(
            static function (RuleGroup $group) use ($filter) {
                app('log')->debug(sprintf('Now filtering group #%d', $group->id));
                // filter the rules in the rule group:
                $group->rules = $group->rules->filter(
                    static function (Rule $rule) use ($filter) {
                        app('log')->debug(sprintf('Now filtering rule #%d', $rule->id));
                        foreach ($rule->ruleTriggers as $trigger) {
                            if ('user_action' === $trigger->trigger_type && $filter === $trigger->trigger_value) {
                                app('log')->debug(sprintf('Rule #%d triggers on %s, include it.', $rule->id, $filter));

                                return true;
                            }
                        }
                        app('log')->debug(sprintf('Rule #%d does not trigger on %s, do not include it.', $rule->id, $filter));

                        return false;
                    }
                );

                return $group;
            }
        );
    }

    public function getHighestOrderRuleGroup(): int
    {
        $entry = $this->user->ruleGroups()->max('order');

        return (int) $entry;
    }

    public function getRuleGroupsWithRules(?string $filter): Collection
    {
        $groups = $this->user->ruleGroups()
            ->orderBy('order', 'ASC')
            ->where('active', true)
            ->with(
                [
                    'rules'              => static function (HasMany $query): void {
                        $query->orderBy('order', 'ASC');
                    },
                    'rules.ruleTriggers' => static function (HasMany $query): void {
                        $query->orderBy('order', 'ASC');
                    },
                    'rules.ruleActions'  => static function (HasMany $query): void {
                        $query->orderBy('order', 'ASC');
                    },
                ]
            )->get()
        ;
        if (null === $filter) {
            return $groups;
        }
        app('log')->debug(sprintf('Will filter getRuleGroupsWithRules on "%s".', $filter));

        return $groups->map(
            static function (RuleGroup $group) use ($filter) {
                app('log')->debug(sprintf('Now filtering group #%d', $group->id));
                // filter the rules in the rule group:
                $group->rules = $group->rules->filter(
                    static function (Rule $rule) use ($filter) {
                        app('log')->debug(sprintf('Now filtering rule #%d', $rule->id));
                        foreach ($rule->ruleTriggers as $trigger) {
                            if ('user_action' === $trigger->trigger_type && $filter === $trigger->trigger_value) {
                                app('log')->debug(sprintf('Rule #%d triggers on %s, include it.', $rule->id, $filter));

                                return true;
                            }
                        }
                        app('log')->debug(sprintf('Rule #%d does not trigger on %s, do not include it.', $rule->id, $filter));

                        return false;
                    }
                );

                return $group;
            }
        );
    }

    public function getRules(RuleGroup $group): Collection
    {
        return $group->rules()
            ->get(['rules.*'])
        ;
    }

    public function maxOrder(): int
    {
        return (int) $this->user->ruleGroups()->where('active', true)->max('order');
    }

    public function searchRuleGroup(string $query, int $limit): Collection
    {
        $search = $this->user->ruleGroups();
        if ('' !== $query) {
            $search->whereLike('rule_groups.title', sprintf('%%%s%%', $query));
        }
        $search->orderBy('rule_groups.order', 'ASC')
            ->orderBy('rule_groups.title', 'ASC')
        ;

        return $search->take($limit)->get(['id', 'title', 'description']);
    }

    public function setUser(null|Authenticatable|User $user): void
    {
        if ($user instanceof User) {
            $this->user = $user;
        }
    }

    public function store(array $data): RuleGroup
    {
        $newRuleGroup = new RuleGroup(
            [
                'user_id'       => $this->user->id,
                'user_group_id' => $this->user->user_group_id,
                'title'         => $data['title'],
                'description'   => $data['description'],
                'order'         => 31337,
                'active'        => array_key_exists('active', $data) ? $data['active'] : true,
            ]
        );
        $newRuleGroup->save();
        $this->resetOrder();
        if (array_key_exists('order', $data)) {
            $this->setOrder($newRuleGroup, $data['order']);
        }

        return $newRuleGroup;
    }

    public function setOrder(RuleGroup $ruleGroup, int $newOrder): void
    {
        $oldOrder         = $ruleGroup->order;

        if ($newOrder > $oldOrder) {
            $this->user->ruleGroups()->where('rule_groups.order', '<=', $newOrder)->where('rule_groups.order', '>', $oldOrder)
                ->where('rule_groups.id', '!=', $ruleGroup->id)
                ->decrement('order')
            ;
            $ruleGroup->order = $newOrder;
            app('log')->debug(sprintf('Order of group #%d ("%s") is now %d', $ruleGroup->id, $ruleGroup->title, $newOrder));
            $ruleGroup->save();

            return;
        }

        $this->user->ruleGroups()->where('rule_groups.order', '>=', $newOrder)->where('rule_groups.order', '<', $oldOrder)
            ->where('rule_groups.id', '!=', $ruleGroup->id)
            ->increment('order')
        ;
        $ruleGroup->order = $newOrder;
        app('log')->debug(sprintf('Order of group #%d ("%s") is now %d', $ruleGroup->id, $ruleGroup->title, $newOrder));
        $ruleGroup->save();
    }

    public function update(RuleGroup $ruleGroup, array $data): RuleGroup
    {
        // update the account:
        if (array_key_exists('title', $data)) {
            $ruleGroup->title = $data['title'];
        }
        if (array_key_exists('description', $data)) {
            $ruleGroup->description = $data['description'];
        }
        if (array_key_exists('active', $data)) {
            $ruleGroup->active = $data['active'];
        }
        // order
        if (array_key_exists('order', $data) && $ruleGroup->order !== $data['order']) {
            $this->resetOrder();
            $this->setOrder($ruleGroup, (int) $data['order']);
        }

        $ruleGroup->save();

        return $ruleGroup;
    }
}
