<?php

namespace FireflyIII\Database\TransactionCurrency;

use FireflyIII\Database\CommonDatabaseCallsInterface;
use FireflyIII\Database\CUDInterface;
use FireflyIII\Exception\NotImplementedException;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;

/**
 * Class TransactionType
 *
 * @package FireflyIII\Database
 */
class TransactionCurrency implements TransactionCurrencyInterface, CommonDatabaseCallsInterface, CUDInterface
{

    /**
     * @param Eloquent $model
     *
     * @return bool
     */
    public function destroy(Eloquent $model)
    {
        $model->delete();
    }

    /**
     * @param array $data
     *
     * @return Eloquent
     */
    public function store(array $data)
    {
        $currency = new \TransactionCurrency($data);
        $currency->save();

        return $currency;
    }

    /**
     * @param Eloquent $model
     * @param array    $data
     *
     * @return bool
     */
    public function update(Eloquent $model, array $data)
    {
        $model->symbol = $data['symbol'];
        $model->code   = $data['code'];
        $model->name   = $data['name'];
        $model->save();

        return true;
    }

    /**
     * Validates an array. Returns an array containing MessageBags
     * errors/warnings/successes.
     *
     * @param array $model
     *
     * @return array
     */
    public function validate(array $model)
    {
        $warnings  = new MessageBag;
        $successes = new MessageBag;
        \Log::debug('Now in TransactionCurrency::validate()');

        $currency = new \TransactionCurrency($model);
        $currency->isValid();
        $errors = $currency->getErrors();

        \Log::debug('Data: ' . json_encode($model));
        \Log::debug('Error-content: ' . json_encode($errors->all()));
        \Log::debug('Error count is: ' . $errors->count());

        $fields = ['name', 'code', 'symbol'];
        foreach ($fields as $field) {
            if (!$errors->has($field)) {
                $successes->add($field, 'OK');
            }
        }

        return ['errors' => $errors, 'warnings' => $warnings, 'successes' => $successes];
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * Returns an object with id $id.
     *
     * @param int $objectId
     *
     * @return \Eloquent
     */
    public function find($objectId)
    {
        return \TransactionCurrency::find($objectId);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * Finds an account type using one of the "$what"'s: expense, asset, revenue, opening, etc.
     *
     * @param $what
     * @throws NotImplementedException
     * @codeCoverageIgnore
     *
     * @return \AccountType|null
     */
    public function findByWhat($what)
    {
        throw new NotImplementedException;
    }

    /**
     * Returns all objects.
     *
     * @return Collection
     */
    public function get()
    {
        return \TransactionCurrency::orderBy('code', 'ASC')->get();
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param array $objectIds
     * @throws NotImplementedException
     * @codeCoverageIgnore
     *
     * @return Collection
     */
    public function getByIds(array $objectIds)
    {
        throw new NotImplementedException;
    }

    /**
     * @param string $code
     *
     * @return \TransactionCurrency|null
     */
    public function findByCode($code)
    {
        return \TransactionCurrency::whereCode($code)->first();
    }
}
