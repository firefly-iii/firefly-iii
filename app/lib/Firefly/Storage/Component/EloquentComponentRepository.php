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
        if (!isset($data['class'])) {
            throw new \Firefly\Exception\FireflyException('No class type present.');
        }
        switch ($data['class']) {
            case 'Budget':
                $component = new \Budget;
                break;
            case 'Category':
                $component = new \Category;
                break;

        }
        $component->name = $data['name'];
        $component->user()->associate(\Auth::user());
        try {
            $component->save();
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error('DB ERROR: ' . $e->getMessage());
            throw new \Firefly\Exception\FireflyException('Could not save component ' . $data['name'] . ' of type'
                . $data['class']);
        }

        return $component;
    }

}