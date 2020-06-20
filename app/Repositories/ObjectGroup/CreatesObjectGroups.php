<?php
declare(strict_types=1);

namespace FireflyIII\Repositories\ObjectGroup;

use FireflyIII\Models\ObjectGroup;
use FireflyIII\User;

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
        return $this->user->objectGroups()->where('title', $title)->first();
    }


    /**
     * @param User   $user
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
                    'user_id' => $this->user->id,
                    'title'   => $title,
                    'order'   => $maxOrder + 1,
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
        return (int) $this->user->objectGroups()->max('order');
    }

    /**
     * @param string $title
     *
     * @return bool
     */
    protected function hasObjectGroup(string $title): bool
    {
        return 1 === $this->user->objectGroups()->where('title', $title)->count();
    }
}
