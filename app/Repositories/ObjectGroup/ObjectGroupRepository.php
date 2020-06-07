<?php
declare(strict_types=1);

namespace FireflyIII\Repositories\ObjectGroup;

use FireflyIII\Models\ObjectGroup;
use Illuminate\Support\Collection;

/**
 * Class ObjectGroupRepository
 */
class ObjectGroupRepository implements ObjectGroupRepositoryInterface
{


    /**
     * @inheritDoc
     */
    public function get(): Collection
    {
        return ObjectGroup::orderBy('order')->get();
    }

    /**
     * @param string $query
     *
     * @return Collection
     */
    public function search(string $query): Collection
    {
        $dbQuery = ObjectGroup::orderBy('order');
        if ('' !== $query) {
            // split query on spaces just in case:
            $parts = explode(' ', $query);
            foreach ($parts as $part) {
                $search = sprintf('%%%s%%', $part);
                $dbQuery->where('title', 'LIKE', $search);
            }

        }

        return $dbQuery->get(['object_groups.*']);
    }
}
