<?php

namespace FireflyIII\Shared\Toolkit;
use Illuminate\Support\Collection;


/**
 * Class Form
 *
 * @package FireflyIII\Shared\Toolkit
 */
class Form
{
    /**
     * Takes any collection and tries to make a sensible select list compatible array of it.
     *
     * @param Collection $set
     * @param bool       $addEmpty
     *
     * @return mixed
     */
    public function makeSelectList(Collection $set, $addEmpty = false)
    {
        $selectList = [];
        if ($addEmpty) {
            $selectList[0] = '(none)';
        }
        $fields = ['title', 'name', 'description'];
        /** @var \Eloquent $entry */
        foreach ($set as $entry) {
            $id    = intval($entry->id);
            $title = null;

            foreach ($fields as $field) {
                if (is_null($title) && isset($entry->$field)) {
                    $title = $entry->$field;
                }
            }
            $selectList[$id] = $title;
        }


        return $selectList;
    }
}
