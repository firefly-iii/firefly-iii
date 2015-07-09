<?php namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class TransactionType
 *
 * @package FireflyIII\Models
 */
class TransactionType extends Model
{
    use SoftDeletes;

    /**
     * @return array
     */
    public function getDates()
    {
        return ['created_at', 'updated_at', 'deleted_at'];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactionJournals()
    {
        return $this->hasMany('FireflyIII\Models\TransactionJournal');
    }
}
