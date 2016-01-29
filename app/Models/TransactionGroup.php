<?php namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * FireflyIII\Models\TransactionGroup
 *
 * @property integer                                                            $id
 * @property \Carbon\Carbon                                                     $created_at
 * @property \Carbon\Carbon                                                     $updated_at
 * @property \Carbon\Carbon                                                     $deleted_at
 * @property integer                                                            $user_id
 * @property string                                                             $relation
 * @property-read \Illuminate\Database\Eloquent\Collection|TransactionJournal[] $transactionjournals
 * @property-read \FireflyIII\User                                              $user
 */
class TransactionGroup extends Model
{
    use SoftDeletes;

    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

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
