<?php
/**
 * Category.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Models;

use Auth;
use Crypt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Watson\Validating\ValidatingTrait;

/**
 * FireflyIII\Models\Category
 *
 * @property integer                                                                        $id
 * @property \Carbon\Carbon                                                                 $created_at
 * @property \Carbon\Carbon                                                                 $updated_at
 * @property \Carbon\Carbon                                                                 $deleted_at
 * @property string                                                                         $name
 * @property integer                                                                        $user_id
 * @property boolean                                                                        $encrypted
 * @property-read \Illuminate\Database\Eloquent\Collection|TransactionJournal[]             $transactionjournals
 * @property-read \FireflyIII\User                                                          $user
 * @property string                                                                         $dateFormatted
 * @property string                                                                         $spent
 * @property \Carbon\Carbon                                                                 $lastActivity
 * @property string                                                                         $type
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Category whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Category whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Category whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Category whereDeletedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Category whereName($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Category whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Category whereEncrypted($value)
 * @mixin \Eloquent
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\Transaction[] $transactions
 */
class Category extends Model
{
    use SoftDeletes, ValidatingTrait;

    protected $dates    = ['created_at', 'updated_at', 'deleted_at'];
    protected $fillable = ['user_id', 'name'];
    protected $hidden   = ['encrypted'];
    protected $rules    = ['name' => 'required|between:1,200',];

    /**
     * @param array $fields
     *
     * @return Category
     */
    public static function firstOrCreateEncrypted(array $fields)
    {
        // everything but the name:
        $query  = Category::orderBy('id');
        $search = $fields;
        unset($search['name']);
        foreach ($search as $name => $value) {
            $query->where($name, $value);
        }
        $set = $query->get(['categories.*']);
        /** @var Category $category */
        foreach ($set as $category) {
            if ($category->name == $fields['name']) {
                return $category;
            }
        }
        // create it!
        $category = Category::create($fields);

        return $category;

    }

    /**
     * @param Category $value
     *
     * @return Category
     */
    public static function routeBinder(Category $value)
    {
        if (Auth::check()) {
            if ($value->user_id == Auth::user()->id) {
                return $value;
            }
        }
        throw new NotFoundHttpException;
    }

    /**
     *
     * @param $value
     *
     * @return string
     */
    public function getNameAttribute($value)
    {

        if (intval($this->encrypted) == 1) {
            return Crypt::decrypt($value);
        }

        return $value;
    }

    /**
     *
     * @param $value
     */
    public function setNameAttribute($value)
    {
        $this->attributes['name']      = Crypt::encrypt($value);
        $this->attributes['encrypted'] = true;
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
