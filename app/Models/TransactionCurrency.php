<?php namespace FireflyIII\Models;

use Auth;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * FireflyIII\Models\TransactionCurrency
 *
 * @property integer                              $id
 * @property Carbon                               $created_at
 * @property Carbon                               $updated_at
 * @property Carbon                               $deleted_at
 * @property string                               $code
 * @property string                               $name
 * @property string                               $symbol
 * @property-read Collection|TransactionJournal[] $transactionJournals
 */
class TransactionCurrency extends Model
{
    use SoftDeletes;


    protected $fillable = ['name', 'code', 'symbol'];
    protected $dates    = ['created_at', 'updated_at', 'deleted_at'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactionJournals()
    {
        return $this->hasMany('FireflyIII\Models\TransactionJournal');
    }

    /**
     * @param TransactionCurrency $currency
     *
     * @return TransactionCurrency
     */
    public static function routeBinder(TransactionCurrency $currency)
    {
        if (Auth::check()) {
            return $currency;
        }
        throw new NotFoundHttpException;
    }
}
