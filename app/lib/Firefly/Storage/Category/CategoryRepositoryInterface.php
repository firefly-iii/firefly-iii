<?php

namespace Firefly\Storage\Category;
use Illuminate\Queue\Jobs\Job;

/**
 * Interface CategoryRepositoryInterface
 *
 * @package Firefly\Storage\Category
 */
interface CategoryRepositoryInterface
{
    /**
     * Takes a transaction/category component and updates the transaction journal to match.
     *
     * @param Job   $job
     * @param array $payload
     *
     * @return mixed
     */
    public function importUpdateTransaction(Job $job, array $payload);

    /**
     * Takes a transfer/category component and updates the transaction journal to match.
     *
     * @param Job   $job
     * @param array $payload
     *
     * @return mixed
     */
    public function importUpdateTransfer(Job $job, array $payload);

    /**
     * @param Job   $job
     * @param array $payload
     *
     * @return mixed
     */
    public function importCategory(Job $job, array $payload);

    /**
     * @return mixed
     */
    public function get();

    /**
     * @param $categoryId
     *
     * @return mixed
     */
    public function find($categoryId);

    /**
     * @param $name
     *
     * @return mixed
     */
    public function createOrFind($name);

    /**
     * @param \User $user
     * @return mixed
     */
    public function overruleUser(\User $user);

    /**
     * @param $name
     *
     * @return mixed
     */
    public function findByName($name);

    /**
     * @param $data
     *
     * @return mixed
     */
    public function store($data);

    /**
     * @param $category
     * @param $data
     *
     * @return mixed
     */
    public function update($category, $data);

    /**
     * @param $category
     *
     * @return mixed
     */
    public function destroy($category);

} 