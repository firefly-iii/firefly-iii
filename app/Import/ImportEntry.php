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

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\User;
use Illuminate\Support\Collection;
use Log;

/**
 * Class ImportEntry
 *
 * @package FireflyIII\Import
 */
class ImportEntry
{
    /** @var array */
    public $certain = [];
    /** @var  array */
    public $fields = [];
    /** @var  User */
    public $user;
    /** @var bool */
    public $valid = true;
    /** @var array */
    private $validFields
        = ['amount',
           'date-transaction',
           'date-interest',
           'date-book',
           'description',
           'date-process',
           'transaction-type',
           'currency', 'asset-account', 'opposing-account', 'bill', 'budget', 'category', 'tags'];

    /**
     * ImportEntry constructor.
     */
    public function __construct()
    {
        $this->defaultImportAccount = new Account;
        /** @var string $value */
        foreach ($this->validFields as $value) {
            $this->fields[$value]  = null;
            $this->certain[$value] = 0;
        }
    }

    /**
     * @return ImportResult
     */
    public function import(): ImportResult
    {

        $validation = $this->validate();

        if ($validation->valid()) {
            return $this->doImport();
        }

        return $validation;
    }

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
                $this->setFloat('amount', $convertedValue, $certainty);

                return;
            case 'account-id':
            case 'account-iban':
            case 'account-name':
                $this->setObject('asset-account', $convertedValue, $certainty);
                break;
            case 'opposing-number':
            case 'opposing-iban':
            case 'opposing-id':
            case 'opposing-name':
                $this->setObject('opposing-account', $convertedValue, $certainty);
                break;
            case 'bill-id':
            case 'bill-name':
                $this->setObject('bill', $convertedValue, $certainty);
                break;
            case 'budget-id':
            case 'budget-name':
                $this->setObject('budget', $convertedValue, $certainty);
                break;
            case 'category-id':
            case 'category-name':
                $this->setObject('category', $convertedValue, $certainty);
                break;
            case 'currency-code':
            case 'currency-id':
            case 'currency-name':
            case 'currency-symbol':
                $this->setObject('currency', $convertedValue, $certainty);
                break;
            case 'date-transaction':
                $this->setDate('date-transaction', $convertedValue, $certainty);
                break;

            case 'date-interest':
                $this->setDate('date-interest', $convertedValue, $certainty);
                break;
            case 'date-book':
                $this->setDate('date-book', $convertedValue, $certainty);
                break;
            case 'date-process':
                $this->setDate('date-process', $convertedValue, $certainty);
                break;
            case'description':
                $this->setAppendableString('description', $convertedValue);
                break;
            case '_ignore':
                break;
            case 'ing-debet-credit':
            case 'rabo-debet-credit':
                $this->manipulateFloat('amount', 'multiply', $convertedValue);
                break;
            case 'tags-comma':
            case 'tags-space':
                $this->appendCollection('tags', $convertedValue);
            case 'external-id':
                // ignore for now.
                break;

        }
    }

    /**
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
    }

    /**
     * @param string     $field
     * @param Collection $convertedValue
     */
    private function appendCollection(string $field, Collection $convertedValue)
    {
        if (is_null($this->fields[$field])) {
            $this->fields[$field] = new Collection;
        }
        $this->fields[$field] = $this->fields[$field]->merge($convertedValue);
    }

    /**
     * @param string $field
     * @param string $action
     * @param        $convertedValue
     *
     * @throws FireflyException
     */
    private function manipulateFloat(string $field, string $action, $convertedValue)
    {
        switch ($action) {
            default:
                Log::error('Cannot handle manipulateFloat', ['field' => $field, 'action' => $action]);
                throw new FireflyException('Cannot manipulateFloat with action ' . $action);
            case'multiply':
                $this->fields[$field] = $this->fields[$field] * $convertedValue;
                break;
        }
    }

    /**
     * @param string $field
     * @param string $value
     */
    private function setAppendableString(string $field, string $value)
    {
        $value = trim($value);
        $this->fields[$field] .= ' ' . $value;
    }

    /**
     * @param string  $field
     * @param  Carbon $date
     * @param int     $certainty
     */
    private function setDate(string $field, Carbon $date, int $certainty)
    {
        if ($certainty > $this->certain[$field] && !is_null($date)) {
            Log::debug(sprintf('ImportEntry: %s is now %s with certainty %d', $field, $date->format('Y-m-d'), $certainty));
            $this->fields[$field]  = $date;
            $this->certain[$field] = $certainty;

            return;
        }
        Log::error(sprintf('Will not set %s based on certainty %d (current certainty is %d) or NULL id.', $field, $certainty, $this->certain[$field]));

    }

    /**
     * @param string $field
     * @param float  $value
     * @param int    $certainty
     */
    private function setFloat(string $field, float $value, int $certainty)
    {
        if ($certainty > $this->certain[$field]) {
            Log::debug(sprintf('ImportEntry: %s is now %f with certainty %d', $field, $value, $certainty));
            $this->fields[$field]  = $value;
            $this->certain[$field] = $certainty;

            return;
        }
        Log::error(sprintf('Will not set %s based on certainty %d (current certainty is %d).', $field, $certainty, $this->certain[$field]));
    }

    /**
     * @param string $field
     * @param        $object
     * @param int    $certainty
     */
    private function setObject(string $field, $object, int $certainty)
    {
        if ($certainty > $this->certain[$field] && !is_null($object->id)) {
            Log::debug(sprintf('ImportEntry: %s ID is now %d with certainty %d', $field, $object->id, $certainty));
            $this->fields[$field]  = $object;
            $this->certain[$field] = $certainty;

            return;
        }
        Log::error(sprintf('Will not set %s based on certainty %d (current certainty is %d) or NULL id.', $field, $certainty, $this->certain[$field]));

    }
}
