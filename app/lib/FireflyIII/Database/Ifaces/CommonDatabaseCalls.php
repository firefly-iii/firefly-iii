<?php

namespace FireflyIII\Database\Ifaces;

use Illuminate\Support\Collection;
use LaravelBook\Ardent\Ardent;


/**
 * Interface CommonDatabaseCalls
 *
 * @package FireflyIII\Database
 */
interface CommonDatabaseCalls
{
    /**
     * Returns an object with id $id.
     *
     * @param int $id
     *
     * @return Ardent
     */
    public function find($id);

    /**
     * Returns all objects.
     *
     * @return Collection
     */
    public function get();


    /**
     * @param array $ids
     *
     * @return Collection
     */
    public function getByIds(array $ids);

    /**
     * Finds an account type using one of the "$what"'s: expense, asset, revenue, opening, etc.
     *
     * @param $what
     *
     * @return \AccountType|null
     */
    public function findByWhat($what);

}