<?php

namespace FireflyIII\Database\Ifaces;



/**
 * Interface CUD
 *
 * @package FireflyIII\Database
 */
interface CUD
{

    /**
     * @param \Eloquent $model
     *
     * @return bool
     */
    public function destroy(\Eloquent $model);

    /**
     * @param array $data
     *
     * @return \Eloquent
     */
    public function store(array $data);

    /**
     * @param \Eloquent $model
     * @param array  $data
     *
     * @return bool
     */
    public function update(\Eloquent $model, array $data);

    /**
     * Validates an array. Returns an array containing MessageBags
     * errors/warnings/successes.
     *
     * @param array $model
     *
     * @return array
     */
    public function validate(array $model);

}