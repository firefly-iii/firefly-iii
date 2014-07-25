<?php


namespace Firefly\Storage\Component;

/**
 * Interface ComponentRepositoryInterface
 *
 * @package Firefly\Storage\Component
 */
interface ComponentRepositoryInterface
{

    /**
     * @return mixed
     */
    public function count();

    /**
     * @return mixed
     */
    public function get();

    /**
     * @param $data
     *
     * @return mixed
     */
    public function store($data);

} 