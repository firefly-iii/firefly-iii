<?php

namespace FireflyIII\Database\Ifaces;

use Illuminate\Support\Collection;



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
     * @return \Eloquent
     */
    public function find($id);

    /**
     * Finds an account type using one of the "$what"'s: expense, asset, revenue, opening, etc.
     *
     * @param $what
     *
     * @return \AccountType|null
     */
    public function findByWhat($what);

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

}