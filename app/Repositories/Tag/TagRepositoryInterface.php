<?php

namespace FireflyIII\Repositories\Tag;
use FireflyIII\Models\Tag;


/**
 * Interface TagRepositoryInterface
 *
 * @package FireflyIII\Repositories\Tag
 */
interface TagRepositoryInterface {

    /**
     * @param array $data
     *
     * @return Tag
     */
    public function store(array $data);
}