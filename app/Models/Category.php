<?php namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Crypt;
/**
 * Class Category
 *
 * @package FireflyIII\Models
 */
class Category extends Model
{
    use SoftDeletes;

    protected $fillable = ['user_id', 'name'];

    /**
     * @return array
     */
    public function getDates()
    {
        return ['created_at', 'updated_at', 'deleted_at'];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function transactionjournals()
    {
        return $this->belongsToMany('FireflyIII\Models\TransactionJournal', 'category_transaction_journal', 'category_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('FireflyIII\User');
    }

    /**
     * @param $value
     */
    public function setNameAttribute($value)
    {
        $this->attributes['name']      = env('DB_ENCRYPTION', true) ? Crypt::encrypt($value) : $value;
        $this->attributes['encrypted'] = env('DB_ENCRYPTION', true);
    }
    /**
     * @param $value
     *
     * @return string
     */
    public function getNameAttribute($value)
    {

        if (intval($this->encrypted) == 1) {
            return Crypt::decrypt($value);
        }

        // @codeCoverageIgnoreStart
        return $value;
        // @codeCoverageIgnoreEnd
    }

}
