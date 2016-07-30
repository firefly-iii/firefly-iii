<?php
/**
 * ImportEntry.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Import;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Category;
use Log;

/**
 * Class ImportEntry
 *
 * @package FireflyIII\Import
 */
class ImportEntry
{
    /** @var  float */
    public $amount;

    /** @var  Account */
    public $assetAccount;

    /** @var int */
    public $assetAccountCertainty = 0;
    /** @var  Bill */
    public $bill;
    /** @var  int */
    public $billCertainty;
    /** @var  Budget */
    public $budget;
    /** @var  int */
    public $budgetCertainty;
    /** @var  Account */
    public $opposingAccount;
    /** @var int */
    public $opposingAccountCertainty = 0;

    /** @var  Category */
    public $category;
    /** @var  int */
    public $categoryCertainty;

    /**
     * @param string $role
     * @param string $value
     * @param int    $certainty
     * @param        $convertedValue
     *
     * @throws FireflyException
     */
    public function importValue(string $role, string $value, int $certainty, $convertedValue)
    {
        Log::debug('Going to import', ['role' => $role, 'value' => $value, 'certainty' => $certainty]);

        switch ($role) {
            default:
                Log::error('Import entry cannot handle object.', ['role' => $role]);
                throw new FireflyException('Import entry cannot handle object of type "' . $role . '".');
                break;

            case 'amount':
                /*
                 * Easy enough.
                 */
                $this->setAmount($convertedValue);

                return;
            case 'account-id':
            case 'account-iban':
            case 'account-name':
                $this->setAssetAccount($convertedValue, $certainty);
                break;
            case 'opposing-number':
                $this->setOpposingAccount($convertedValue, $certainty);
                break;
            case 'bill-id':
            case 'bill-name':
                $this->setBill($convertedValue, $certainty);
                break;
            case 'budget-id':
            case 'budget-name':
                $this->setObject('budget', 'budgetCertainty', $convertedValue, $certainty);
                //$this->setBudget($convertedValue, $certainty);
                break;
            case 'category-id':
            case 'category-name':
                $this->setObject('category', 'categoryCertainty', $convertedValue, $certainty);
                break;

        }
    }

    /**
     * @param float $amount
     */
    private function setAmount(float $amount)
    {
        $this->amount = $amount;
    }

    /**
     * @param Account $account
     * @param int     $certainty
     */
    private function setAssetAccount(Account $account, int $certainty)
    {
        if ($certainty > $this->assetAccountCertainty && !is_null($account->id)) {
            Log::debug(sprintf('ImportEntry: Asset Account ID is now %d with certainty %d', $account->id, $certainty));
            $this->assetAccount          = $account;
            $this->assetAccountCertainty = $certainty;

            return;
        }
        Log::error(sprintf('Will not set asset account based on certainty %d (current certainty is %d) or NULL id.', $certainty, $this->assetAccountCertainty));
    }

    /**
     * @param Bill $bill
     * @param int  $certainty
     */
    private function setBill(Bill $bill, int $certainty)
    {
        if ($certainty > $this->billCertainty && !is_null($bill->id)) {
            Log::debug(sprintf('ImportEntry: Bill-ID is now %d with certainty %d', $bill->id, $certainty));
            $this->bill          = $bill;
            $this->billCertainty = $certainty;

            return;
        }
        Log::error(sprintf('Will not set bill based on certainty %d (current certainty is %d) or NULL id.', $certainty, $this->billCertainty));
    }

    /**
     * @param Budget $budget
     * @param int    $certainty
     */
    private function setBudget(Budget $budget, int $certainty)
    {
        if ($certainty > $this->budgetCertainty && !is_null($budget->id)) {
            Log::debug(sprintf('ImportEntry: Budget-ID is now %d with certainty %d', $budget->id, $certainty));
            $this->budget          = $budget;
            $this->budgetCertainty = $certainty;

            return;
        }
        Log::error(sprintf('Will not set budget based on certainty %d (current certainty is %d) or NULL id.', $certainty, $this->budgetCertainty));
    }

    /**
     * @param string $field
     * @param string $cert
     * @param        $object
     * @param int    $certainty
     */
    private function setObject(string $field, string $cert, $object, int $certainty)
    {
        if ($certainty > $this->$cert && !is_null($object->id)) {
            Log::debug(sprintf('ImportEntry: %s ID is now %d with certainty %d', $field, $object->id, $certainty));
            $this->$field = $object;
            $this->$cert  = $certainty;

            return;
        }
        Log::error(sprintf('Will not set %s based on certainty %d (current certainty is %d) or NULL id.', $field, $certainty, $this->$cert));

    }

    /**
     * @param Account $account
     * @param int     $certainty
     */
    private function setOpposingAccount(Account $account, int $certainty)
    {
        if ($certainty > $this->opposingAccountCertainty && !is_null($account->id)) {
            Log::debug(sprintf('ImportEntry: Opposing Account ID is now %d with certainty %d', $account->id, $certainty));
            $this->assetAccount          = $account;
            $this->assetAccountCertainty = $certainty;

            return;
        }
        Log::error(
            sprintf('Will not set opposing account based on certainty %d (current certainty is %d) or NULL id.', $certainty, $this->assetAccountCertainty)
        );
    }


}