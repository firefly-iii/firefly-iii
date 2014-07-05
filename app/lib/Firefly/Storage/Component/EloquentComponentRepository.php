<?php


namespace Firefly\Storage\Component;

class EloquentComponentRepository implements ComponentRepositoryInterface
{
    public $validator;

    public function __construct()
    {
    }

    public function count()
    {
        return \Auth::user()->accounts()->count();

    }


    public function store($data)
    {
        if (!isset($data['component_type'])) {
            throw new \Firefly\Exception\FireflyException('No component type present.');
        }
        $component = new \Component;
        $component->componentType()->associate($data['component_type']);
        $component->name = $data['name'];
        $component->user()->associate(\Auth::user());
        try {
            $component->save();
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error('DB ERROR: ' . $e->getMessage());
            throw new \Firefly\Exception\FireflyException('Could not save component ' . $data['name']);
        }

        return $component;
    }

}