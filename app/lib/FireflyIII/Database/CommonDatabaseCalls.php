<?php

namespace FireflyIII\Database;

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
     * @param int $objectId
     *
     * @return \Eloquent
     */
    public function find($objectId);

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
     * @param array $objectIds
     *
     * @return Collection
     */
    public function getByIds(array $objectIds);

}