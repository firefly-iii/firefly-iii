<?php
/**
 * TransactionType.php
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

namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class TransactionType.
 */
class TransactionType extends Model
{
    use SoftDeletes;

    const WITHDRAWAL      = 'Withdrawal';
    const DEPOSIT         = 'Deposit';
    const TRANSFER        = 'Transfer';
    const OPENING_BALANCE = 'Opening balance';

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts
        = [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];

    /**
     * @param string $type
     *
     * @return Model|null|static
     */
    public static function routeBinder(string $type)
    {
        if (!auth()->check()) {
            throw new NotFoundHttpException;
        }
        $transactionType = self::where('type', ucfirst($type))->first();
        if (null !== $transactionType) {
            return $transactionType;
        }
        throw new NotFoundHttpException;
    }

    /**
     * @return bool
     */
    public function isDeposit()
    {
        return self::DEPOSIT === $this->type;
    }

    /**
     * @return bool
     */
    public function isOpeningBalance()
    {
        return self::OPENING_BALANCE === $this->type;
    }

    /**
     * @return bool
     */
    public function isTransfer()
    {
        return self::TRANSFER === $this->type;
    }

    /**
     * @return bool
     */
    public function isWithdrawal()
    {
        return self::WITHDRAWAL === $this->type;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactionJournals()
    {
        return $this->hasMany('FireflyIII\Models\TransactionJournal');
    }
}
