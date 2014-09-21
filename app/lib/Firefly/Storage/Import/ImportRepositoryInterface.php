<?php

namespace Firefly\Storage\Import;

/**
 * Interface ImportRepositoryInterface
 * @package Firefly\Storage\Import
 */
interface ImportRepositoryInterface
{


    /**
     * @param \Importmap $map
     * @param $class
     * @param $oldID
     * @param $newID
     * @return mixed
     */
    public function store(\Importmap $map, $class, $oldID, $newID);

    /**
     * @param $id
     *
     * @return mixed
     */
    public function findImportMap($id);

    /**
     * @param \Importmap $map
     * @param            $class
     * @param            $oldID
     *
     * @return mixed
     */
    public function findImportEntry(\Importmap $map, $class, $oldID);

    /**
     * @param \Importmap $map
     * @param            $oldComponentId
     *
     * @return mixed
     */
    public function findImportComponentMap(\Importmap $map, $oldComponentId);

    /**
     * @param \User $user
     * @return mixed
     */
    public function overruleUser(\User $user);
} 