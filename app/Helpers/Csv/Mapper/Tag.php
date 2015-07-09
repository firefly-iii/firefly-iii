<?php

namespace FireflyIII\Helpers\Csv\Mapper;

use Auth;
use FireflyIII\Models\Tag as TagModel;

/**
 * Class Tag
 *
 * @package FireflyIII\Helpers\Csv\Mapper
 */
class Tag implements MapperInterface
{

    /**
     * @return array
     */
    public function getMap()
    {
        $result = Auth::user()->budgets()->get(['tags.*']);
        $list   = [];

        /** @var TagModel $tag */
        foreach ($result as $tag) {
            $list[$tag->id] = $tag->tag;
        }
        asort($list);

        $list = [0 => trans('firefly.csv_do_not_map')] + $list;

        return $list;
    }
}
