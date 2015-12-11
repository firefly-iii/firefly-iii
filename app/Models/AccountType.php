<?php namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class AccountType
 *
 * @package FireflyIII\Models
 * @property integer                                                                    $id
 * @property \Carbon\Carbon                                                             $created_at
 * @property \Carbon\Carbon                                                             $updated_at
 * @property string                                                                     $type
 * @property boolean                                                                    $editable
 * @property-read \Illuminate\Database\Eloquent\Collection|\FireflyIII\Models\Account[] $accounts
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\AccountType whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\AccountType whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\AccountType whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\AccountType whereType($value)
 * @method static \Illuminate\Database\Query\Builder|\FireflyIII\Models\AccountType whereEditable($value)
 */
class AccountType extends Model
{
    const DEFAULT_ACCOUNT = 'Default account';
    const CASH = 'Cash account';
    const ASSET = 'Asset account';
    const EXPENSE = 'Expense account';
    const REVENUE = 'Revenue account';
    const INITIAL_BALANCE = 'Initial balance account';
    const BENEFICIARY = 'Beneficiary account';
    const IMPORT = 'Import account';

    //
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function accounts()
    {
        return $this->hasMany('FireflyIII\Models\Account');
    }

    /**
     * @return array
     */
    public function getDates()
    {
        return ['created_at', 'updated_at'];
    }

    /**
     * Gets asset account types.
     * @return array Returns a list of asset account types.
     */
    public static function getAssetAccounts()
    {
        return [AccountType::DEFAULT_ACCOUNT, AccountType::ASSET];
    }

    /**
     * Gets expense account types.
     * @return array Returns a list of expense account types.
     */
    public static function getExpenseAccounts()
    {
        return [self::EXPENSE, self::BENEFICIARY];
    }

    /**
     * Gets all account types.
     * @return array Returns a list of expense account types.
     */
    public static function getAllAccounts()
    {
        return [self::DEFAULT_ACCOUNT, self::ASSET, self::CASH];
    }

    /**
     * @param Account $account
     * @return bool
     */
    public static function allowTransfer(Account $account)
    {
        return in_array($account->getAccountType(), AccountType::getAssetAccounts());
    }

    /**
     * Is a Default account?
     * @return bool Returns true if type is Default account otherwise false.
     */
    public function isDefault()
    {
        return $this->type === self::DEFAULT_ACCOUNT;
    }

    /**
     * Is a Cash account?
     * @return bool Returns true if type is Cash account otherwise false.
     */
    public function isCash()
    {
        return $this->type === self::CASH;
    }

    /**
     * Is a Asset account?
     * @return bool Returns true if type is Asset account otherwise false.
     */
    public function isAsset()
    {
        return $this->type === self::ASSET;
    }

    /**
     * Is a Expense account?
     * @return bool Returns true if type is Expense account otherwise false.
     */
    public function isExpense()
    {
        return $this->type === self::EXPENSE;
    }

    /**
     * Is a Revenue account?
     * @return bool Returns true if type is Revenue account otherwise false.
     */
    public function isRevenue()
    {
        return $this->type === self::REVENUE;
    }

    /**
     * Is a Initial balance account?
     * @return bool Returns true if type is Initial balance account otherwise false.
     */
    public function isInitialBalance()
    {
        return $this->type === self::INITIAL_BALANCE;
    }

    /**
     * Is a Beneficiary account?
     * @return bool Returns true if type is Beneficiary account otherwise false.
     */
    public function isBeneficiary()
    {
        return $this->type === self::BENEFICIARY;
    }

    /**
     * Is a Import account?
     * @return bool Returns true if type is Import account otherwise false.
     */
    public function isImport()
    {
        return $this->type === self::IMPORT;
    }
}
