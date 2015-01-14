<?php

namespace FireflyIII\Database\AccountMeta;

use FireflyIII\Database\CUDInterface;
use FireflyIII\Exception\NotImplementedException;
use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * Class AccountMeta
 *
 * @package FireflyIII\Database\AccountMeta
 */
class AccountMeta implements CUDInterface
{

    /**
     * @param Eloquent $model
     *
     * @return bool
     */
    public function destroy(Eloquent $model)
    {
        // TODO: Implement destroy() method.
        throw new NotImplementedException;
    }

    /**
     * @param array $data
     *
     * @return Eloquent
     */
    public function store(array $data)
    {
        // TODO: Implement store() method.
        throw new NotImplementedException;
    }

    /**
     * @param Eloquent $model
     * @param array    $data
     *
     * @return bool
     */
    public function update(Eloquent $model, array $data)
    {
        // TODO: Implement update() method.
        throw new NotImplementedException;
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
        $model = new \AccountMeta($model);
        $model->isValid();

        return ['errors' => $model->getErrors()];
    }
}