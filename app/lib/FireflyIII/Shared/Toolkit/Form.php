<?php

namespace FireflyIII\Shared\Toolkit;

use Illuminate\Support\Collection;

/**
 * Class Form
 *
 * @package FireflyIII\Shared\Toolkit
 */
class Form {
    /**
     * Takes any collection and tries to make a sensible select list compatible array of it.
     *
     * @param Collection $set
     * @param null $titleField
     *
     * @return mixed
     */
    public function makeSelectList(Collection $set, $titleField = null)
    {
        $selectList = [];
        /** @var Model $entry */
        foreach ($set as $entry) {
            $id    = intval($entry->id);
            $title = null;
            if (is_null($titleField)) {
                // try 'title' field.
                if (isset($entry->title)) {
                    $title = $entry->title;
                }
                // try 'name' field
                if (is_null($title)) {
                    $title = $entry->name;
                }

                // try 'description' field
                if (is_null($title)) {
                    $title = $entry->description;
                }
            } else {
                $title = $entry->$titleField;
            }
            $selectList[$id] = $title;
        }
        return $selectList;
    }

} 