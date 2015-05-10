<?php namespace FireflyIII\Models;

use App;
use Crypt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Category
 *
 *
 * @package FireflyIII\Models
 */
class Category extends Model
{
    use SoftDeletes;

    protected $fillable = ['user_id', 'name'];

    /**
     * @codeCoverageIgnore
     * @return array
     */
    public function getDates()
    {
        return ['created_at', 'updated_at', 'deleted_at'];
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
     * @param array $fields
     *
     * @return Account|null
     */
    public static function firstOrCreateEncrypted(array $fields)
    {
        // everything but the name:
        $query = Category::orderBy('id');
        foreach ($fields as $name => $value) {
            if ($name != 'name') {
                $query->where($name, $value);
            }
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
        if (is_null($category->id)) {
            // could not create account:
            App::abort(500, 'Could not create new category with data: ' . json_encode($fields));

        }

        return $category;

    }

    /**
     * @codeCoverageIgnore
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('FireflyIII\User');
    }

    /**
     * @codeCoverageIgnore
     * @param $value
     */
    public function setNameAttribute($value)
    {
        $this->attributes['name']      = Crypt::encrypt($value);
        $this->attributes['encrypted'] = true;
    }

    /**
     * @codeCoverageIgnore
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

}
