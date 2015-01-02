<?php
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\SoftDeletingTrait;
use Watson\Validating\ValidatingTrait;

/**
 * Class TransactionCurrency
 */
class TransactionCurrency extends Eloquent
{

    use SoftDeletingTrait, ValidatingTrait;

    protected $fillable = ['name', 'symbol', 'code'];
    protected $rules = [
                'code'   => 'required|alpha|between:3,3|min:3|max:3',
                'name'   => 'required|between:3,48|min:3|max:48',
                'symbol' => 'required|between:1,8|min:1|max:8',
        ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactionJournals()
    {
        return $this->hasMany('TransactionJournal');
    }

} 
