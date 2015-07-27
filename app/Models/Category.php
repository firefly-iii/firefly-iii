<?php namespace FireflyIII\Models;

use Crypt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Category
 *
 * @package FireflyIII\Models
 * @property integer                                                                               $id
 * @property \Carbon\Carbon                                                                        $created_at
 * @property \Carbon\Carbon                                                                        $updated_at
 * @property \Carbon\Carbon                                                                        $deleted_at
 * @property string                                                                                $name
 * @property integer                                                                               $user_id
 * @property boolean                                                                               $encrypted
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\TransactionJournal[] $transactionjournals
 * @property-read \FireflyIII\User                                                                 $user
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Category whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Category whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Category whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Category whereDeletedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Category whereName($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Category whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\Category whereEncrypted($value)
 * @property-read float                                                                            $spent
 * @property-read \Carbon\Carbon                                                                   $lastActivity
 */
class Category extends Model
{
    use SoftDeletes;

    protected $fillable = ['user_id', 'name'];
    protected $hidden   = ['encrypted'];

    /**
     * @param array $fields
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
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
     * @codeCoverageIgnore
     * @return string[]
     */
    public function getDates()
    {
        return ['created_at', 'updated_at', 'deleted_at'];
    }

    /**
     * @codeCoverageIgnore
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
     * @codeCoverageIgnore
     *
     * @param $value
     */
    public function setNameAttribute($value)
    {
        $this->attributes['name']      = Crypt::encrypt($value);
        $this->attributes['encrypted'] = true;
    }

    /**
     * @codeCoverageIgnore
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function transactionjournals()
    {
        return $this->belongsToMany('FireflyIII\Models\TransactionJournal', 'category_transaction_journal', 'category_id');
    }

    /**
     * @codeCoverageIgnore
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('FireflyIII\User');
    }

}
