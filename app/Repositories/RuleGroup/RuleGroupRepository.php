<?php
/**
 * RuleGroupRepository.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Repositories\RuleGroup;


use FireflyIII\Models\Rule;
use FireflyIII\Models\RuleGroup;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * Class RuleGroupRepository
 *
 * @package FireflyIII\Repositories\RuleGroup
 */
class RuleGroupRepository implements RuleGroupRepositoryInterface
{
    /** @var User */
    private $user;

    /**
     * BillRepository constructor.
     *
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return $this->user->ruleGroups()->count();
    }

    /**
     * @param RuleGroup $ruleGroup
     * @param RuleGroup $moveTo
     *
     * @return bool
     */
    public function destroy(RuleGroup $ruleGroup, RuleGroup $moveTo = null): bool
    {
        /** @var Rule $rule */
        foreach ($ruleGroup->rules as $rule) {

            if (is_null($moveTo)) {

                $rule->delete();
                continue;
            }
            // move
            $rule->ruleGroup()->associate($moveTo);
            $rule->save();
        }

        $ruleGroup->delete();

        $this->resetRuleGroupOrder();
        if (!is_null($moveTo)) {
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
        if (is_null($group)) {
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
            $count++;
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
            $count++;
        }

        return true;

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
