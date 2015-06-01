<?php namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class TransactionCurrency
 *
 * @codeCoverageIgnore 
 * @package FireflyIII\Models
 * @property integer $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $deleted_at
 * @property string $code
 * @property string $name
 * @property string $symbol
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\TransactionJournal[] $transactionJournals
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\TransactionCurrency whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\TransactionCurrency whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\TransactionCurrency whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\TransactionCurrency whereDeletedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\TransactionCurrency whereCode($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\TransactionCurrency whereName($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\TransactionCurrency whereSymbol($value)
 */
class TransactionCurrency extends Model
{
    use SoftDeletes;


    protected $fillable = ['name', 'code', 'symbol'];

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
