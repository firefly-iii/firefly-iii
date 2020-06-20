<?php
declare(strict_types=1);

namespace FireflyIII\Repositories\ObjectGroup;

use FireflyIII\Models\ObjectGroup;
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

    /**
     * Delete empty ones.
     */
    public function deleteEmpty(): void;

    /**
     * Sort
     */
    public function sort(): void;

    /**
     * @param ObjectGroup $objectGroup
     * @param int         $index
     *
     * @return ObjectGroup
     */
    public function setOrder(ObjectGroup $objectGroup, int $index): ObjectGroup;

    /**
     * @param ObjectGroup $objectGroup
     * @param array       $data
     *
     * @return ObjectGroup
     */
    public function update(ObjectGroup $objectGroup, array $data): ObjectGroup;

    /**
     * @param ObjectGroup $objectGroup
     */
    public function destroy(ObjectGroup $objectGroup): void;

}
