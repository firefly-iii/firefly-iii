<?php
/**
 * AccountTransformer.php
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

namespace FireflyIII\Transformers;


use FireflyIII\Models\Account;
use FireflyIII\Models\Note;
use FireflyIII\Models\TransactionCurrency;
use League\Fractal\TransformerAbstract;

/**
 * Class AccountTransformer
 */
class AccountTransformer extends TransformerAbstract
{
    /**
     * @param Account $account
     *
     * @return array
     */
    public function transform(Account $account): array
    {
        $role = $account->getMeta('accountRole');
        if (strlen($role) === 0) {
            $role = null;
        }
        $currencyId   = (int)$account->getMeta('currency_id');
        $currencyCode = null;
        if ($currencyId > 0) {
            $currency     = TransactionCurrency::find($currencyId);
            $currencyCode = $currency->code;
        }

        if ($currencyId === 0) {
            $currencyId = null;
        }


        $data = [
            'id'            => (int)$account->id,
            'updated_at'    => $account->updated_at->toAtomString(),
            'created_at'    => $account->created_at->toAtomString(),
            'name'          => $account->name,
            'active'        => intval($account->active) === 1,
            'type'          => $account->accountType->type,
            'currency_id'   => $currencyId,
            'currency_code' => $currencyCode,
            'notes'         => null,
            'role'          => $role,
            'links'         => [
                [
                    'rel' => 'self',
                    'uri' => '/accounts/' . $account->id,
                ],
            ],
        ];
        /** @var Note $note */
        $note = $account->notes()->first();
        if (!is_null($note)) {
            $data['notes'] = $note->text;
        }

        return $data;
    }

}