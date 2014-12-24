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
    protected $rules
                        = [
            'creating' => [
                'code'   => 'required|min:3|max:3|alpha|unique:transaction_currencies,code',
                'name'   => 'required|min:3|max:48|unique:transaction_currencies,name',
                'symbol' => 'required|min:1|max:8|unique:transaction_currencies,symbol',
            ],

            'updating' => [
                'code'   => 'required|min:3|max:3|alpha',
                'name'   => 'required|min:3|max:48',
                'symbol' => 'required|min:1|max:8',
            ],

        ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactionJournals()
    {
        return $this->hasMany('TransactionJournal');
    }

} 