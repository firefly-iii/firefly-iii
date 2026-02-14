<?php

declare(strict_types=1);

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

use FireflyIII\Support\Facades\Amount;
use FireflyIII\Support\Http\Api\ExchangeRateConverter;
use Illuminate\Support\Facades\Log;

class ConvertsAmountToPrimaryAmount
{
    public static function convert(ConversionParameters $params): void
    {
        $amountField                          = $params->amountField;
        $primaryAmountField                   = $params->primaryAmountField;

        if (!Amount::convertToPrimary($params->user)) {
            //            Log::debug(sprintf(
            //                'User does not want to do conversion, no need to convert "%s" and store it in field "%s" for %s #%d.',
            //                $params->amountField,
            //                $params->primaryAmountField,
            //                get_class($params->model),
            //                $params->model->id
            //            ));
            $params->model->{$primaryAmountField} = null;
            $params->model->saveQuietly();

            return;
        }
        if (null === $params->originalCurrency) {
            Log::debug(sprintf(
                'Original currency field is empty, no need to convert %s and store it in field %s for %s #%d.',
                $params->amountField,
                $params->primaryAmountField,
                get_class($params->model),
                $params->model->id
            ));

            return;
        }
        $primaryCurrency                      = Amount::getPrimaryCurrencyByUserGroup($params->user->userGroup);
        Log::debug(sprintf(
            'Will convert amount in field %s from %s to %s and store it in %s',
            $params->originalCurrency->code,
            $primaryCurrency->code,
            $params->amountField,
            $params->primaryAmountField
        ));
        if ($params->originalCurrency->id === $primaryCurrency->id) {
            Log::debug('Both currencies are the same, do nothing.');

            return;
        }

        // field is empty or zero, do nothing.
        $amount                               = (string) $params->model->{$amountField};
        if ('' === $amount || 0 === bccomp($amount, '0')) {
            Log::debug(sprintf('Amount "%s" in field "%s" cannot be used, do nothing.', $amount, $amountField));
            $params->model->{$amountField}        = null;
            $params->model->{$primaryAmountField} = null;
            $params->model->saveQuietly();

            return;
        }
        $converter                            = new ExchangeRateConverter();
        $converter->setUserGroup($params->user->userGroup);
        $converter->setIgnoreSettings(true);
        $newAmount                            = $converter->convert($params->originalCurrency, $primaryCurrency, now(), $amount);
        $params->model->{$primaryAmountField} = $newAmount;
        $params->model->saveQuietly();
        Log::debug(sprintf(
            'Converted field "%s" of %s #%d from %s %s to %s %s (in field "%s")',
            $amountField,
            get_class($params->model),
            $params->model->id,
            $params->originalCurrency->code,
            $amount,
            $primaryCurrency->code,
            $newAmount,
            $primaryAmountField
        ));
    }
}
