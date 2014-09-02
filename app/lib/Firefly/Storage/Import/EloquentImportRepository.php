<?php

namespace Firefly\Storage\Import;


class EloquentImportRepository implements ImportRepositoryInterface
{

    public function findImportComponentMap(\Importmap $map, $oldComponentId)
    {
        $entry = \Importentry::where('importmap_id', $map->id)
                             ->whereIn('class', ['Budget', 'Category', 'Account', 'Component'])
                             ->where('old', intval($oldComponentId))->first();

        return $entry;
    }

    public function findImportEntry(\Importmap $map, $class, $oldID)
    {

        return \Importentry::where('importmap_id', $map->id)->where('class', $class)->where('old', $oldID)->first();
    }

    public function findImportMap($id)
    {
        return \Importmap::find($id);
    }

    public function store(\Importmap $map, $class, $oldID, $newID)
    {
        $entry = new \Importentry;
        $entry->importmap()->associate($map);
        $entry->class = $class;
        $entry->old   = intval($oldID);
        $entry->new   = intval($newID);
        $entry->save();
    }

} 