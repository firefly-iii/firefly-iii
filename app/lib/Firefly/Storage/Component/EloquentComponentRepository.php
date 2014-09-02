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
    protected $_user = null;

    /**
     *
     */
    public function __construct()
    {
        $this->_user = \Auth::user();
    }

    /**
     * @return mixed
     */
    public function count()
    {
        return $this->_user->components()->count();

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
     * @param \User $user
     * @return mixed|void
     */
    public function overruleUser(\User $user)
    {
        $this->_user = $user;
        return true;
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
        $component->user()->associate($this->_user);
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