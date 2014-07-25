<?php


namespace Firefly\Storage\Component;

use Firefly\Exception\FireflyException;
use Illuminate\Database\QueryException;

/**
 * Class EloquentComponentRepository
 *
 * @package Firefly\Storage\Component
 */
class EloquentComponentRepository implements ComponentRepositoryInterface
{
    public $validator;

    /**
     *
     */
    public function __construct()
    {
    }

    /**
     * @return mixed
     */
    public function count()
    {
        return \Auth::user()->components()->count();

    }

    /**
     * @return mixed|void
     * @throws \Firefly\Exception\FireflyException
     */
    public function get()
    {
        throw new FireflyException('No implementation.');
    }

    /**
     * @param $data
     *
     * @return \Budget|\Category|mixed
     * @throws \Firefly\Exception\FireflyException
     */
    public function store($data)
    {
        if (!isset($data['class'])) {
            throw new FireflyException('No class type present.');
        }
        switch ($data['class']) {
            default:
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
        } catch (QueryException $e) {
            \Log::error('DB ERROR: ' . $e->getMessage());
            throw new FireflyException('Could not save component ' . $data['name'] . ' of type'
                . $data['class']);
        }

        return $component;
    }

}