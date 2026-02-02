<?php
/*
 * ConvertsAmountToPrimaryAmount.php
 * Copyright (c) 2026 james@firefly-iii.org
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

namespace FireflyIII\Handlers\ExchangeRate;

use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Support\Facades\Amount;
use FireflyIII\Support\Http\Api\ExchangeRateConverter;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class ConvertsAmountToPrimaryAmount
{
    public static function convert(User $user, Model $model, TransactionCurrency $originalCurrency, string $amountField, string $primaryAmountField): void
    {
        if (!Amount::convertToPrimary($user)) {
            Log::debug(sprintf('User does not want to do conversion, no need to convert %s and store it in field %s.', $amountField, $primaryAmountField));
            return;
        }
        $primaryCurrency = Amount::getPrimaryCurrencyByUserGroup($user->userGroup);
        Log::debug(sprintf('Will convert amount in field %s from %s to %s and store it in %s', $originalCurrency->code, $primaryCurrency->code, $amountField, $primaryAmountField));
        if ($originalCurrency->id === $primaryCurrency->id) {
            Log::debug('Both currencies are the same, do nothing.');
            return;
        }
        // field is empty or zero, do nothing.
        $amount = (string)$model->$amountField;
        if ('' === $amount || 0 !== bccomp($amount, '0')) {
            Log::debug(sprintf('Amount "%s" in field "%s" cannot be used, do nothing.', $amount, $amountField));
            $model->$amountField        = null;
            $model->$primaryAmountField = null;
            $model->saveQuietly();
            return;
        }
        $converter = new ExchangeRateConverter();
        $converter->setUserGroup($user->userGroup);
        $converter->setIgnoreSettings(true);
        $model->$primaryAmountField = $converter->convert($originalCurrency, $primaryCurrency, today(), $amount);
        $model->saveQuietly();
    }

}
