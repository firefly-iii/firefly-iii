<?php
use Carbon\Carbon;
use FireflyIII\Shared\SingleTableInheritanceEntity;
use Illuminate\Database\Eloquent\SoftDeletingTrait;
use Watson\Validating\ValidatingTrait;

class Component extends SingleTableInheritanceEntity
{

    public static $rules
                                 = [
            'user_id' => 'exists:users,id|required',
            'name'    => 'required|between:1,100|alphabasic',
            'class'   => 'required',
        ];
    protected     $dates         = ['deleted_at', 'created_at', 'updated_at'];
    protected     $fillable      = ['name', 'user_id'];
    protected     $subclassField = 'class';
    protected     $table         = 'components';
    use SoftDeletingTrait, ValidatingTrait;

    /**
     * TODO remove this method in favour of something in the FireflyIII libraries.
     *
     * @return Carbon
     */
    public function lastActionDate()
    {
        $transaction = $this->transactionjournals()->orderBy('updated_at', 'DESC')->first();
        if (is_null($transaction)) {
            return null;
        }

        return $transaction->date;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function transactionjournals()
    {
        return $this->belongsToMany('TransactionJournal', 'component_transaction_journal', 'component_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function transactions()
    {
        return $this->belongsToMany('Transaction');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('User');
    }

} 