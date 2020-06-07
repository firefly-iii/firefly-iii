<?php
declare(strict_types=1);

namespace FireflyIII\Repositories\ObjectGroup;

/**
 * Trait OrganisesRoleGroups
 */
trait OrganisesObjectGroups
{
    /**
     *
     */
    protected function cleanupObjectGroups(): void
    {
        $this->deleteEmptyObjectGroups();
        $this->sortObjectGroups();
    }

    /**
     *
     */
    private function deleteEmptyObjectGroups(): void
    {
        $repository = app(ObjectGroupRepositoryInterface::class);
        $repository->deleteEmpty();
    }

    /**
     *
     */
    private function sortObjectGroups(): void
    {
        $repository = app(ObjectGroupRepositoryInterface::class);
        $repository->sort();
    }
}
