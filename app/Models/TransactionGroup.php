<?php namespace FireflyIII\Models;

use Carbon\Carbon;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * FireflyIII\Models\TransactionGroup
 *
 * @property integer                              $id
 * @property Carbon                               $created_at
 * @property Carbon                               $updated_at
 * @property Carbon                               $deleted_at
 * @property integer                              $user_id
 * @property string                               $relation
 * @property-read Collection|TransactionJournal[] $transactionjournals
 * @property-read User                            $user
 */
class TransactionGroup extends Model
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
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function transactionjournals()
    {
        return $this->belongsToMany('FireflyIII\Models\TransactionJournal');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('FireflyIII\User');
    }

}
