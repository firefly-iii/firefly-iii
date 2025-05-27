<?php

/**
 * FrontpageController.php
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

namespace FireflyIII\Http\Controllers\Json;

use Throwable;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use FireflyIII\Support\Facades\Amount;
use Illuminate\Http\JsonResponse;

/**
 * Class FrontpageController.
 */
class FrontpageController extends Controller
{
    /**
     * Piggy bank pie chart.
     *
     * @throws FireflyException
     */
    public function piggyBanks(PiggyBankRepositoryInterface $repository): JsonResponse
    {
        $set             = $repository->getPiggyBanks();
        $info            = [];
        $native          = Amount::getNativeCurrency();
        $convertToNative = Amount::convertToNative();


        /** @var PiggyBank $piggyBank */
        foreach ($set as $piggyBank) {
            $amount       = $repository->getCurrentAmount($piggyBank);
            $nativeAmount = $repository->getCurrentNativeAmount($piggyBank);
            if (1 === bccomp($amount, '0')) {
                // percentage!
                $pct    = 0;
                if (0 !== bccomp((string) $piggyBank->target_amount, '0')) {
                    $pct = (int) bcmul(bcdiv($amount, (string) $piggyBank->target_amount), '100');
                }

                $entry  = [
                    'id'                             => $piggyBank->id,
                    'name'                           => $piggyBank->name,
                    'amount'                         => $amount,
                    'native_amount'                  => $nativeAmount,
                    'target'                         => $piggyBank->target_amount,
                    'native_target'                  => $piggyBank->native_target_amount,
                    'percentage'                     => $pct,
                    // currency:
                    'currency_symbol'                => $piggyBank->transactionCurrency->symbol,
                    'currency_decimal_places'        => $piggyBank->transactionCurrency->decimal_places,
                    'native_currency_symbol'         => $native->symbol,
                    'native_currency_decimal_places' => $native->decimal_places,

                ];

                $info[] = $entry;
            }
        }

        // sort by current percentage (lowest at the top)
        uasort(
            $info,
            static fn (array $a, array $b) => $a['percentage'] <=> $b['percentage']
        );

        $html            = '';
        if (0 !== count($info)) {
            try {
                $html = view('json.piggy-banks', compact('info', 'convertToNative', 'native'))->render();
            } catch (Throwable $e) {
                app('log')->error(sprintf('Cannot render json.piggy-banks: %s', $e->getMessage()));
                app('log')->error($e->getTraceAsString());
                $html = 'Could not render view.';

                throw new FireflyException($html, 0, $e);
            }
        }

        return response()->json(['html' => $html]);
    }
}
