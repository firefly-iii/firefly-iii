<?php
/**
 * Category.php
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

use Crypt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Watson\Validating\ValidatingTrait;

/**
 * Class Category.
 */
class Category extends Model
{
    use SoftDeletes, ValidatingTrait;

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
            'encrypted'  => 'boolean',
        ];
    /** @var array */
    protected $fillable = ['user_id', 'name'];
    /** @var array */
    protected $hidden = ['encrypted'];
    /** @var array */
    protected $rules = ['name' => 'required|between:1,200'];

    /**
     * @param array $fields
     *
     * @return Category
     */
    public static function firstOrCreateEncrypted(array $fields)
    {
        // everything but the name:
        $query  = self::orderBy('id');
        $search = $fields;
        unset($search['name']);
        foreach ($search as $name => $value) {
            $query->where($name, $value);
        }
        $set = $query->get(['categories.*']);
        /** @var Category $category */
        foreach ($set as $category) {
            if ($category->name === $fields['name']) {
                return $category;
            }
        }
        // create it!
        $category = self::create($fields);

        return $category;
    }

    /**
     * @param Category $value
     *
     * @return Category
     */
    public static function routeBinder(self $value)
    {
        if (auth()->check()) {
            if (intval($value->user_id) === auth()->user()->id) {
                return $value;
            }
        }
        throw new NotFoundHttpException;
    }

    /**
     * @param $value
     *
     * @return string
     */
    public function getNameAttribute($value)
    {
        if ($this->encrypted) {
            return Crypt::decrypt($value);
        }

        return $value;
    }

    /**
     * @param $value
     */
    public function setNameAttribute($value)
    {
        $encrypt                       = config('firefly.encryption');
        $this->attributes['name']      = $encrypt ? Crypt::encrypt($value) : $value;
        $this->attributes['encrypted'] = $encrypt;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function transactionJournals()
    {
        return $this->belongsToMany('FireflyIII\Models\TransactionJournal', 'category_transaction_journal', 'category_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function transactions()
    {
        return $this->belongsToMany('FireflyIII\Models\Transaction', 'category_transaction', 'category_id');
    }

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo('FireflyIII\User');
    }
}
