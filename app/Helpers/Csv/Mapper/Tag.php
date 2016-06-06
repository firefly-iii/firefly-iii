<?php
/**
 * Tag.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);
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
    public function getMap(): array
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
