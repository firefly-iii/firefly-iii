<?php
/**
 * RuleGroupRepository.php
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

namespace FireflyIII\Repositories\RuleGroup;

use FireflyIII\Models\Rule;
use FireflyIII\Models\RuleGroup;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * Class RuleGroupRepository.
 */
class RuleGroupRepository implements RuleGroupRepositoryInterface
{
    /** @var User */
    private $user;

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
     * @param int $ruleGroupId
     *
     * @return RuleGroup
     */
    public function find(int $ruleGroupId): RuleGroup
    {
        $group = $this->user->ruleGroups()->find($ruleGroupId);
        if (null === $group) {
            return new RuleGroup;
        }

        return $group;
    }

    /**
     * @return Collection
     */
    public function get(): Collection
    {
        return $this->user->ruleGroups()->orderBy('order', 'ASC')->get();
    }

    /**
     * @param User $user
     *
     * @return Collection
     */
    public function getActiveGroups(User $user): Collection
    {
        return $user->ruleGroups()->where('rule_groups.active', 1)->orderBy('order', 'ASC')->get(['rule_groups.*']);
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

        return intval($entry);
    }

    /**
     * @param User $user
     *
     * @return Collection
     */
    public function getRuleGroupsWithRules(User $user): Collection
    {
        return $user->ruleGroups()
                    ->orderBy('active', 'DESC')
                    ->orderBy('order', 'ASC')
                    ->with(
                        [
                            'rules'              => function (HasMany $query) {
                                $query->orderBy('active', 'DESC');
                                $query->orderBy('order', 'ASC');
                            },
                            'rules.ruleTriggers' => function (HasMany $query) {
                                $query->orderBy('order', 'ASC');
                            },
                            'rules.ruleActions'  => function (HasMany $query) {
                                $query->orderBy('order', 'ASC');
                            },
                        ]
                    )->get();
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
        $other = $this->user->ruleGroups()->where('order', ($order + 1))->first();
        if ($other) {
            $other->order = ($other->order - 1);
            $other->save();
        }

        $ruleGroup->order = ($ruleGroup->order + 1);
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
        $other = $this->user->ruleGroups()->where('order', ($order - 1))->first();
        if ($other) {
            $other->order = ($other->order + 1);
            $other->save();
        }

        $ruleGroup->order = ($ruleGroup->order - 1);
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

        $set   = $this->user->ruleGroups()->where('active', 1)->orderBy('order', 'ASC')->get();
        $count = 1;
        /** @var RuleGroup $entry */
        foreach ($set as $entry) {
            $entry->order = $count;
            $entry->save();
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
     * @param User $user
     */
    public function setUser(User $user)
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
                'order'       => ($order + 1),
                'active'      => 1,
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
        $ruleGroup->title       = $data['title'];
        $ruleGroup->description = $data['description'];
        $ruleGroup->active      = $data['active'];
        $ruleGroup->save();
        $this->resetRuleGroupOrder();

        return $ruleGroup;
    }
}
