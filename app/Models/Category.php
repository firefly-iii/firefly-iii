<?php namespace FireflyIII\Models;

use Auth;
use Carbon\Carbon;
use Crypt;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * FireflyIII\Models\Category
 *
 * @property integer                              $id
 * @property Carbon                               $created_at
 * @property Carbon                               $updated_at
 * @property Carbon                               $deleted_at
 * @property string                               $name
 * @property integer                              $user_id
 * @property boolean                              $encrypted
 * @property-read Collection|TransactionJournal[] $transactionjournals
 * @property-read User                            $user
 * @property string                               $dateFormatted
 * @property float                                $spent
 * @property Carbon                               $lastActivity
 * @property string                               $type
 */
class Category extends Model
{
    use SoftDeletes;

    protected $dates    = ['created_at', 'updated_at', 'deleted_at'];
    protected $fillable = ['user_id', 'name'];
    protected $hidden   = ['encrypted'];

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
