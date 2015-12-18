<?php namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class TransactionType
 *
 * @package FireflyIII\Models
 * @property integer                                                                               $id
 * @property \Carbon\Carbon                                                                        $created_at
 * @property \Carbon\Carbon                                                                        $updated_at
 * @property \Carbon\Carbon                                                                        $deleted_at
 * @property string                                                                                $type
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\TransactionJournal[] $transactionJournals
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\TransactionType whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\TransactionType whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\TransactionType whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\TransactionType whereDeletedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\TransactionType whereType($value)
 */
class TransactionType extends Model
{
    use SoftDeletes;

    const WITHDRAWAL = 'Withdrawal';
    const DEPOSIT = 'Deposit';
    const TRANSFER = 'Transfer';
    const OPENING_BALANCE = 'Opening balance';

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

    /**
     * @return bool
     */
    public function isWithdrawal()
    {
        return $this->type === TransactionType::WITHDRAWAL;
    }

    /**
     * @return bool
     */
    public function isDeposit()
    {
        return $this->type === TransactionType::DEPOSIT;
    }

    /**
     * @return bool
     */
    public function isTransfer()
    {
        return $this->type === TransactionType::TRANSFER;
    }

    /**
     * @return bool
     */
    public function isOpeningBalance()
    {
        return $this->type === TransactionType::OPENING_BALANCE;
    }
}
