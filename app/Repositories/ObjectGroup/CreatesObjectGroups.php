<?php
declare(strict_types=1);

namespace FireflyIII\Repositories\ObjectGroup;

use FireflyIII\Models\ObjectGroup;

/**
 * Trait CreatesObjectGroups
 */
trait CreatesObjectGroups
{
    /**
     * @param string $title
     *
     * @return null|ObjectGroup
     */
    protected function findObjectGroup(string $title): ?ObjectGroup
    {
        return ObjectGroup::where('title', $title)->first();
    }

    /**
     * @param string $title
     *
     * @return ObjectGroup|null
     */
    protected function findOrCreateObjectGroup(string $title): ?ObjectGroup
    {
        $group    = null;
        $maxOrder = $this->getObjectGroupMaxOrder();
        if (!$this->hasObjectGroup($title)) {
            return ObjectGroup::create(
                [
                    'title' => $title,
                    'order' => $maxOrder + 1,
                ]
            );
        }

        return $this->findObjectGroup($title);
    }

    /**
     * @return int
     */
    protected function getObjectGroupMaxOrder(): int
    {
        return ObjectGroup::max('order');
    }

    /**
     * @param string $title
     *
     * @return bool
     */
    protected function hasObjectGroup(string $title): bool
    {
        return 1 === ObjectGroup::where('title', $title)->count();
    }
}
