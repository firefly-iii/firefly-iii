<?php

namespace FireflyIII\Database\Ifaces;

use LaravelBook\Ardent\Ardent;

/**
 * Interface CUD
 *
 * @package FireflyIII\Database
 */
interface CUD
{

    /**
     * @param Ardent $model
     *
     * @return bool
     */
    public function destroy(Ardent $model);

    /**
     * @param array $data
     *
     * @return Ardent
     */
    public function store(array $data);

    /**
     * @param Ardent $model
     * @param array  $data
     *
     * @return bool
     */
    public function update(Ardent $model, array $data);

    /**
     * Validates an array. Returns an array containing MessageBags
     * errors/warnings/successes.
     *
     * @param array $model
     *
     * @return array
     */
    public function validate(array $model);

    /**
     * Validates a model. Returns an array containing MessageBags
     * errors/warnings/successes.
     *
     * @param Ardent $model
     *
     * @return array
     */
    public function validateObject(Ardent $model);

} 