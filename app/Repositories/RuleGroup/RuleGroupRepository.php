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

use DB;
use Exception;
use FireflyIII\Models\Rule;
use FireflyIII\Models\RuleGroup;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Log;

/**
 * Class RuleGroupRepository.
 */
class RuleGroupRepository implements RuleGroupRepositoryInterface
{
    private User $user;

    /**
     * @inheritDoc
     */
    public function correctRuleGroupOrder(): void
    {
        $set   = $this->user
            ->ruleGroups()
            ->orderBy('order', 'ASC')
            ->orderBy('active', 'DESC')
            ->orderBy('title', 'ASC')
            ->get(['rule_groups.id']);
        $index = 1;
        /** @var RuleGroup $ruleGroup */
        foreach ($set as $ruleGroup) {
            if ($ruleGroup->order !== $index) {
                $ruleGroup->order = $index;
                $ruleGroup->save();
            }
            $index++;
        }
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return $this->user->ruleGroups()->count();
    }

    /**
     * @param RuleGroup      $ruleGroup
     * @param RuleGroup|null $moveTo
     *
     * @return bool
     * @throws Exception
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

        $this->resetRuleGroupOrder();
        if (null !== $moveTo) {
            $this->resetRulesInGroupOrder($moveTo);
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function destroyAll(): void
    {
        $groups = $this->get();
        /** @var RuleGroup $group */
        foreach ($groups as $group) {
            $group->rules()->delete();
            $group->delete();
        }
    }

    /**
     * @param int $ruleGroupId
     *
     * @return RuleGroup|null
     */
    public function find(int $ruleGroupId): ?RuleGroup
    {
        $group = $this->user->ruleGroups()->find($ruleGroupId);
        if (null === $group) {
            return null;
        }

        return $group;
    }

    /**
     * @param string $title
     *
     * @return RuleGroup|null
     */
    public function findByTitle(string $title): ?RuleGroup
    {
        return $this->user->ruleGroups()->where('title', $title)->first();
    }

    /**
     * @return Collection
     */
    public function get(): Collection
    {
        return $this->user->ruleGroups()->orderBy('order', 'ASC')->get();
    }

    /**
     * @return Collection
     */
    public function getActiveGroups(): Collection
    {
        return $this->user->ruleGroups()->with(['rules'])->where('rule_groups.active', 1)->orderBy('order', 'ASC')->get(['rule_groups.*']);
    }

    /**
     * @param RuleGroup $group
     *
     * @return Collection
     */
    public function getActiveRules(RuleGroup $group): Collection
    {
        return $group->rules()
                     ->where('rules.active', 1)
                     ->get(['rules.*']);
    }

    /**
     * @param RuleGroup $group
     *
     * @return Collection
     */
    public function getActiveStoreRules(RuleGroup $group): Collection
    {
        return $group->rules()
                     ->leftJoin('rule_triggers', 'rules.id', '=', 'rule_triggers.rule_id')
                     ->where('rule_triggers.trigger_type', 'user_action')
                     ->where('rule_triggers.trigger_value', 'store-journal')
                     ->where('rules.active', 1)
                     ->get(['rules.*']);
    }

    /**
     * @param RuleGroup $group
     *
     * @return Collection
     */
    public function getActiveUpdateRules(RuleGroup $group): Collection
    {
        return $group->rules()
                     ->leftJoin('rule_triggers', 'rules.id', '=', 'rule_triggers.rule_id')
                     ->where('rule_triggers.trigger_type', 'user_action')
                     ->where('rule_triggers.trigger_value', 'update-journal')
                     ->where('rules.active', 1)
                     ->get(['rules.*']);
    }

    /**
     * @return int
     */
    public function getHighestOrderRuleGroup(): int
    {
        $entry = $this->user->ruleGroups()->max('order');

        return (int)$entry;
    }

    /**
     * @param string|null $filter
     *
     * @return Collection
     */
    public function getRuleGroupsWithRules(?string $filter): Collection
    {
        $groups = $this->user->ruleGroups()
                             ->orderBy('order', 'ASC')
                             ->where('active', true)
                             ->with(
                                 [
                                     'rules'              => static function (HasMany $query) {
                                         $query->orderBy('order', 'ASC');
                                     },
                                     'rules.ruleTriggers' => static function (HasMany $query) {
                                         $query->orderBy('order', 'ASC');
                                     },
                                     'rules.ruleActions'  => static function (HasMany $query) {
                                         $query->orderBy('order', 'ASC');
                                     },
                                 ]
                             )->get();
        if (null === $filter) {
            return $groups;
        }
        Log::debug(sprintf('Will filter getRuleGroupsWithRules on "%s".', $filter));

        return $groups->map(
            function (RuleGroup $group) use ($filter) {
                Log::debug(sprintf('Now filtering group #%d', $group->id));
                // filter the rules in the rule group:
                $group->rules = $group->rules->filter(
                    function (Rule $rule) use ($filter) {
                        Log::debug(sprintf('Now filtering rule #%d', $rule->id));
                        foreach ($rule->ruleTriggers as $trigger) {
                            if ('user_action' === $trigger->trigger_type && $filter === $trigger->trigger_value) {
                                Log::debug(sprintf('Rule #%d triggers on %s, include it.', $rule->id, $filter));

                                return true;
                            }
                        }
                        Log::debug(sprintf('Rule #%d does not trigger on %s, do not include it.', $rule->id, $filter));

                        return false;
                    }
                );

                return $group;
            }
        );
    }

    /**
     * @param RuleGroup $group
     *
     * @return Collection
     */
    public function getRules(RuleGroup $group): Collection
    {
        return $group->rules()
                     ->get(['rules.*']);
    }

    /**
     * @inheritDoc
     */
    public function maxOrder(): int
    {
        return (int)$this->user->ruleGroups()->max('order');
    }

    /**
     * @param RuleGroup $ruleGroup
     *
     * @return bool
     */
    public function moveDown(RuleGroup $ruleGroup): bool
    {
        $order = $ruleGroup->order;

        // find the rule with order+1 and give it order-1
        $other = $this->user->ruleGroups()->where('order', $order + 1)->first();
        if ($other) {
            --$other->order;
            $other->save();
        }

        ++$ruleGroup->order;
        $ruleGroup->save();
        $this->resetRuleGroupOrder();

        return true;
    }

    /**
     * @param RuleGroup $ruleGroup
     *
     * @return bool
     */
    public function moveUp(RuleGroup $ruleGroup): bool
    {
        $order = $ruleGroup->order;

        // find the rule with order-1 and give it order+1
        $other = $this->user->ruleGroups()->where('order', $order - 1)->first();
        if ($other) {
            ++$other->order;
            $other->save();
        }

        --$ruleGroup->order;
        $ruleGroup->save();
        $this->resetRuleGroupOrder();

        return true;
    }

    /**
     * @return bool
     */
    public function resetRuleGroupOrder(): bool
    {
        $this->user->ruleGroups()->whereNotNull('deleted_at')->update(['order' => 0]);

        $set   = $this->user
            ->ruleGroups()
            ->orderBy('order', 'ASC')->get();
        $count = 1;
        /** @var RuleGroup $entry */
        foreach ($set as $entry) {
            $entry->order = $count;
            $entry->save();

            // also update rules in group.
            $this->resetRulesInGroupOrder($entry);

            ++$count;
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
        $ruleGroup->rules()->whereNotNull('deleted_at')->update(['order' => 0]);

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
     * @inheritDoc
     */
    public function searchRuleGroup(string $query, int $limit): Collection
    {
        $search = $this->user->ruleGroups();
        if ('' !== $query) {
            $search->where('rule_groups.title', 'LIKE', sprintf('%%%s%%', $query));
        }
        $search->orderBy('rule_groups.order', 'ASC')
               ->orderBy('rule_groups.title', 'ASC');

        return $search->take($limit)->get(['id', 'title', 'description']);
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
     * @return RuleGroup
     */
    public function store(array $data): RuleGroup
    {
        $order = $this->getHighestOrderRuleGroup();

        $newRuleGroup = new RuleGroup(
            [
                'user_id'     => $this->user->id,
                'title'       => $data['title'],
                'description' => $data['description'],
                'order'       => $order + 1,
                'active'      => $data['active'],
            ]
        );
        $newRuleGroup->save();
        $this->resetRuleGroupOrder();

        return $newRuleGroup;
    }

    /**
     * @param RuleGroup $ruleGroup
     * @param array     $data
     *
     * @return RuleGroup
     */
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
            $this->correctRuleGroupOrder();
            $max = $this->maxOrder();
            // TODO also for bills and accounts:
            $data['order'] = $data['order'] > $max ? $max : $data['order'];
            $ruleGroup     = $this->updateOrder($ruleGroup, $ruleGroup->order, $data['order']);
        }

        $ruleGroup->save();

        return $ruleGroup;
    }

    /**
     * @inheritDoc
     */
    public function updateOrder(RuleGroup $ruleGroup, int $oldOrder, int $newOrder): RuleGroup
    {
        if ($newOrder > $oldOrder) {
            $this->user->ruleGroups()->where('order', '<=', $newOrder)->where('order', '>', $oldOrder)
                       ->where('rule_groups.id', '!=', $ruleGroup->id)
                       ->update(['order' => DB::raw('rule_groups.order-1')]);
            $ruleGroup->order = $newOrder;
            $ruleGroup->save();
        }
        if ($newOrder < $oldOrder) {
            $this->user->ruleGroups()->where('order', '>=', $newOrder)->where('order', '<', $oldOrder)
                       ->where('rule_groups.id', '!=', $ruleGroup->id)
                       ->update(['order' => DB::raw('rule_groups.order+1')]);
            $ruleGroup->order = $newOrder;
            $ruleGroup->save();
        }

        return $ruleGroup;
    }
}
