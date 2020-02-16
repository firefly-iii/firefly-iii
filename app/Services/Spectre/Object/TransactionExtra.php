<?php
/**
 * TransactionExtra.php
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

namespace FireflyIII\Services\Spectre\Object;

use Carbon\Carbon;

/**
 * Class TransactionExtra
 *
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ShortVariable)
 * @codeCoverageIgnore
 */
class TransactionExtra extends SpectreObject
{
    /** @var string */
    private $accountBalanceSnapshot;
    /** @var string */
    private $accountNumber;
    /** @var string */
    private $additional;
    /** @var string */
    private $assetAmount;
    /** @var string */
    private $assetCode;
    /** @var string */
    private $categorizationConfidence;
    /** @var string */
    private $checkNumber;
    /** @var string */
    private $customerCategoryCode;
    /** @var string */
    private $customerCategoryName;
    /** @var string */
    private $id;
    /** @var string */
    private $information;
    /** @var string */
    private $mcc;
    /** @var string */
    private $originalAmount;
    /** @var string */
    private $originalCategory;
    /** @var string */
    private $originalCurrencyCode;
    /** @var string */
    private $originalSubCategory;
    /** @var string */
    private $payee;
    /** @var bool */
    private $possibleDuplicate;
    /** @var Carbon */
    private $postingDate;
    /** @var Carbon */
    private $postingTime;
    /** @var string */
    private $recordNumber;
    /** @var array */
    private $tags;
    /** @var Carbon */
    private $time;
    /** @var string */
    private $type;
    /** @var string */
    private $unitPrice;
    /** @var string */
    private $units;

    /**
     * TransactionExtra constructor.
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->id                       = $data['id'] ?? null;
        $this->recordNumber             = $data['record_number'] ?? null;
        $this->information              = $data['information'] ?? null;
        $this->time                     = isset($data['time']) ? new Carbon($data['time']) : null;
        $this->postingDate              = isset($data['posting_date']) ? new Carbon($data['posting_date']) : null;
        $this->postingTime              = isset($data['posting_time']) ? new Carbon($data['posting_time']) : null;
        $this->accountNumber            = $data['account_number'] ?? null;
        $this->originalAmount           = $data['original_amount'] ?? null;
        $this->originalCurrencyCode     = $data['original_currency_code'] ?? null;
        $this->assetCode                = $data['asset_code'] ?? null;
        $this->assetAmount              = $data['asset_amount'] ?? null;
        $this->originalCategory         = $data['original_category'] ?? null;
        $this->originalSubCategory      = $data['original_subcategory'] ?? null;
        $this->customerCategoryCode     = $data['customer_category_code'] ?? null;
        $this->customerCategoryName     = $data['customer_category_name'] ?? null;
        $this->possibleDuplicate        = $data['possible_duplicate'] ?? null;
        $this->tags                     = $data['tags'] ?? null;
        $this->mcc                      = $data['mcc'] ?? null;
        $this->payee                    = $data['payee'] ?? null;
        $this->type                     = $data['type'] ?? null;
        $this->checkNumber              = $data['check_number'] ?? null;
        $this->units                    = $data['units'] ?? null;
        $this->additional               = $data['additional'] ?? null;
        $this->unitPrice                = $data['unit_price'] ?? null;
        $this->accountBalanceSnapshot   = $data['account_balance_snapshot'] ?? null;
        $this->categorizationConfidence = $data['categorization_confidence'] ?? null;
    }

    /**
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {

        $array = [
            'id'                        => $this->id,
            'record_number'             => $this->recordNumber,
            'information'               => $this->information,
            'time'                      => null === $this->time ? null : $this->time->toIso8601String(),
            'posting_date'              => null === $this->postingDate ? null : $this->postingDate->toIso8601String(),
            'posting_time'              => null === $this->postingTime ? null : $this->postingTime->toIso8601String(),
            'account_number'            => $this->accountNumber,
            'original_amount'           => $this->originalAmount,
            'original_currency_code'    => $this->originalCurrencyCode,
            'asset_code'                => $this->assetCode,
            'asset_amount'              => $this->assetAmount,
            'original_category'         => $this->originalCategory,
            'original_subcategory'      => $this->originalSubCategory,
            'customer_category_code'    => $this->customerCategoryCode,
            'customer_category_name'    => $this->customerCategoryName,
            'possible_duplicate'        => $this->possibleDuplicate,
            'tags'                      => $this->tags,
            'mcc'                       => $this->mcc,
            'payee'                     => $this->payee,
            'type'                      => $this->type,
            'check_number'              => $this->checkNumber,
            'units'                     => $this->units,
            'additional'                => $this->additional,
            'unit_price'                => $this->unitPrice,
            'account_balance_snapshot'  => $this->accountBalanceSnapshot,
            'categorization_confidence' => $this->categorizationConfidence,
        ];


        return $array;
    }


}
