<?php
/**
 * BillUpdateService.php
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

namespace FireflyIII\Services\Internal\Update;

use DB;
use FireflyIII\Factory\TransactionCurrencyFactory;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Rule;
use FireflyIII\Models\RuleTrigger;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\ObjectGroup\CreatesObjectGroups;
use FireflyIII\Services\Internal\Support\BillServiceTrait;
use FireflyIII\User;
use Illuminate\Support\Collection;
use Log;

/**
 * @codeCoverageIgnore
 * Class BillUpdateService
 */
class BillUpdateService
{
    use BillServiceTrait, CreatesObjectGroups;

    protected User $user;

    /**
     * @param Bill  $bill
     * @param array $data
     *
     * @return Bill
     */
    public function update(Bill $bill, array $data): Bill
    {
        $this->user = $bill->user;
        /** @var TransactionCurrencyFactory $factory */
        $factory = app(TransactionCurrencyFactory::class);
        /** @var TransactionCurrency $currency */
        $currency = $factory->find($data['currency_id'] ?? null, $data['currency_code'] ?? null);

        if (null === $currency) {
            // use default currency:
            $currency = app('amount')->getDefaultCurrencyByUser($bill->user);
        }

        // enable the currency if it isn't.
        $currency->enabled = true;
        $currency->save();

        // new values
        $data['transaction_currency_name'] = $currency->name;
        $bill                              = $this->updateBillProperties($bill, $data);
        $bill->transaction_currency_id     = $currency->id;
        $bill->save();
        // old values
        $oldData = [
            'name'                      => $bill->name,
            'amount_min'                => $bill->amount_min,
            'amount_max'                => $bill->amount_max,
            'transaction_currency_name' => $bill->transactionCurrency->name,
        ];


        // update note:
        if (isset($data['notes'])) {
            $this->updateNote($bill, (string)$data['notes']);
        }

        // update order.
        // update the order of the piggy bank:
        $oldOrder = (int)$bill->order;
        $newOrder = (int)($data['order'] ?? $oldOrder);
        if ($oldOrder !== $newOrder) {
            $this->updateOrder($bill, $oldOrder, $newOrder);
        }

        // update rule actions.
        $this->updateBillActions($bill, $oldData['name'], $data['name']);
        $this->updateBillTriggers($bill, $oldData, $data);

        // update using name:
        $objectGroupTitle = $data['object_group'] ?? '';
        if ('' !== $objectGroupTitle) {
            $objectGroup = $this->findOrCreateObjectGroup($objectGroupTitle);
            if (null !== $objectGroup) {
                $bill->objectGroups()->sync([$objectGroup->id]);
                $bill->save();
            }

            return $bill;
        }
        // remove if name is empty. Should be overruled by ID.
        if ('' === $objectGroupTitle) {
            $bill->objectGroups()->sync([]);
            $bill->save();
        }

        // try also with ID:
        $objectGroupId = (int)($data['object_group_id'] ?? 0);
        if (0 !== $objectGroupId) {
            $objectGroup = $this->findObjectGroupById($objectGroupId);
            if (null !== $objectGroup) {
                $bill->objectGroups()->sync([$objectGroup->id]);
                $bill->save();
            }

            return $bill;
        }
        if (0 === $objectGroupId) {
            $bill->objectGroups()->sync([]);
            $bill->save();
        }

        return $bill;
    }

    /**
     * @param Bill  $bill
     * @param array $oldData
     * @param array $newData
     */
    private function updateBillTriggers(Bill $bill, array $oldData, array $newData): void
    {
        Log::debug(sprintf('Now in updateBillTriggers(%d, "%s")', $bill->id, $bill->name));
        /** @var BillRepositoryInterface $repository */
        $repository = app(BillRepositoryInterface::class);
        $repository->setUser($bill->user);
        $rules = $repository->getRulesForBill($bill);
        if (0 === $rules->count()) {
            Log::debug('Found no rules.');

            return;
        }
        Log::debug(sprintf('Found %d rules', $rules->count()));
        $fields = [
            'name'                      => 'description_contains',
            'amount_min'                => 'amount_more',
            'amount_max'                => 'amount_less',
            'transaction_currency_name' => 'currency_is'];
        foreach ($fields as $field => $ruleTriggerKey) {
            if ($oldData[$field] === $newData[$field]) {
                Log::debug(sprintf('Field %s is unchanged ("%s"), continue.', $field, $oldData[$field]));
                continue;
            }
            $this->updateRules($rules, $ruleTriggerKey, $oldData[$field], $newData[$field]);
        }

    }

    /**
     * @param Collection $rules
     * @param string     $key
     * @param string     $oldValue
     * @param string     $newValue
     */
    private function updateRules(Collection $rules, string $key, string $oldValue, string $newValue): void
    {
        /** @var Rule $rule */
        foreach ($rules as $rule) {
            $trigger = $this->getRuleTrigger($rule, $key);
            if (null !== $trigger && $trigger->trigger_value === $oldValue) {
                Log::debug(sprintf('Updated rule trigger #%d from value "%s" to value "%s"', $trigger->id, $oldValue, $newValue));
                $trigger->trigger_value = $newValue;
                $trigger->save();
                continue;
            }
            if (null !== $trigger && $trigger->trigger_value !== $oldValue && in_array($key, ['amount_more', 'amount_less'], true)
                && 0 === bccomp($trigger->trigger_value, $oldValue)) {
                Log::debug(sprintf('Updated rule trigger #%d from value "%s" to value "%s"', $trigger->id, $oldValue, $newValue));
                $trigger->trigger_value = $newValue;
                $trigger->save();
            }
        }
    }


    /**
     * @param Rule   $rule
     * @param string $key
     *
     * @return RuleTrigger|null
     */
    private function getRuleTrigger(Rule $rule, string $key): ?RuleTrigger
    {
        return $rule->ruleTriggers()->where('trigger_type', $key)->first();
    }

    /**
     * @param Bill $bill
     * @param int  $oldOrder
     * @param int  $newOrder
     */
    private function updateOrder(Bill $bill, int $oldOrder, int $newOrder): void
    {
        if ($newOrder > $oldOrder) {
            $this->user->bills()->where('order', '<=', $newOrder)->where('order', '>', $oldOrder)
                       ->where('bills.id', '!=', $bill->id)
                       ->update(['order' => DB::raw('bills.order-1')]);
            $bill->order = $newOrder;
            $bill->save();
        }
        if ($newOrder < $oldOrder) {
            $this->user->bills()->where('order', '>=', $newOrder)->where('order', '<', $oldOrder)
                       ->where('bills.id', '!=', $bill->id)
                       ->update(['order' => DB::raw('bills.order+1')]);
            $bill->order = $newOrder;
            $bill->save();
        }

    }

    /**
     * @param Bill  $bill
     * @param array $data
     *
     * @return Bill
     */
    private function updateBillProperties(Bill $bill, array $data): Bill
    {

        if (isset($data['name']) && '' !== (string)$data['name']) {
            $bill->name = $data['name'];
        }

        if (isset($data['amount_min']) && '' !== (string)$data['amount_min']) {
            $bill->amount_min = $data['amount_min'];
        }
        if (isset($data['amount_max']) && '' !== (string)$data['amount_max']) {
            $bill->amount_max = $data['amount_max'];
        }
        if (isset($data['date']) && '' !== (string)$data['date']) {
            $bill->date = $data['date'];
        }
        if (isset($data['repeat_freq']) && '' !== (string)$data['repeat_freq']) {
            $bill->repeat_freq = $data['repeat_freq'];
        }
        if (isset($data['skip']) && '' !== (string)$data['skip']) {
            $bill->skip = $data['skip'];
        }
        if (isset($data['active']) && is_bool($data['active'])) {
            $bill->active = $data['active'];
        }

        $bill->match     = 'EMPTY';
        $bill->automatch = true;
        $bill->save();

        return $bill;
    }
}
