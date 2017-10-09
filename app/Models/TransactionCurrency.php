<?php
/**
 * TransactionCurrency.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Watson\Validating\ValidatingTrait;

/**
 * Class TransactionCurrency
 *
 * @package FireflyIII\Models
 */
class TransactionCurrency extends Model
{
    use SoftDeletes;

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts
                        = [
            'created_at'     => 'date',
            'updated_at'     => 'date',
            'deleted_at'     => 'date',
            'decimal_places' => 'int',
        ];
    protected $dates    = ['created_at', 'updated_at', 'deleted_at', 'date'];
    protected $fillable = ['name', 'code', 'symbol', 'decimal_places'];

    /**
     * @param TransactionCurrency $currency
     *
     * @return TransactionCurrency
     */
    public static function routeBinder(TransactionCurrency $currency)
    {
        if (auth()->check()) {
            return $currency;
        }
        throw new NotFoundHttpException;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactionJournals()
    {
        return $this->hasMany('FireflyIII\Models\TransactionJournal');
    }
}
