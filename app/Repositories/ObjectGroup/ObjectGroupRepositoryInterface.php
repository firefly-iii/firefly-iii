<?php
declare(strict_types=1);

namespace FireflyIII\Repositories\ObjectGroup;

use Illuminate\Support\Collection;

/**
 * Interface ObjectGroupRepositoryInterface
 */
interface ObjectGroupRepositoryInterface
{
    /**
     * @return Collection
     */
    public function get(): Collection;

    /**
     * @param string $query
     *
     * @return Collection
     */
    public function search(string $query): Collection;

}
