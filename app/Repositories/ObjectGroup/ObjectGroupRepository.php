<?php
declare(strict_types=1);

namespace FireflyIII\Repositories\ObjectGroup;

use DB;
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
        return ObjectGroup::orderBy('order', 'ASC')->orderBy('title', 'ASC')->get();
    }

    /**
     * @param string $query
     *
     * @return Collection
     */
    public function search(string $query): Collection
    {
        $dbQuery = ObjectGroup::orderBy('order', 'ASC')->orderBy('title', 'ASC');
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

    /**
     * @inheritDoc
     */
    public function deleteEmpty(): void
    {
        $all = $this->get();
        /** @var ObjectGroup $group */
        foreach ($all as $group) {
            $count = DB::table('object_groupables')->where('object_groupables.object_group_id', $group->id)->count();
            if (0 === $count) {
                $group->delete();
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function sort(): void
    {
        $all = $this->get();
        /**
         * @var int         $index
         * @var ObjectGroup $group
         */
        foreach ($all as $index => $group) {
            $group->order = $index + 1;
            $group->save();
        }
    }
}
