<?php
namespace FireflyIII\Shared;

/**
 * Class SingleTableInheritanceEntity
 *
 * @package FireflyIII\Shared
 */
abstract class SingleTableInheritanceEntity extends \Eloquent
{
    /**
     * must be overridden and set to true in subclasses
     *
     * @var bool
     */
    // @codingStandardsIgnoreStart
    protected $isSubclass    = false;
    protected $subclassField = null;
    // @codingStandardsIgnoreEnd

    /**
     * @param array $attributes
     *
     * @return \Illuminate\Database\Eloquent\Model|static
     */
    public function newFromBuilder($attributes = [])
    {
        $instance = $this->mapData((array)$attributes)->newInstance([], true);
        $instance->setRawAttributes((array)$attributes, true);

        return $instance;
    }

    /**
     *
     * instead of using $this->newInstance(), call
     * newInstance() on the object from mapData
     *
     * @param bool $excludeDeleted
     *
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function newQuery($excludeDeleted = true)
    {
        // If using Laravel 4.0.x then use the following commented version of this command
        // $builder = new Builder($this->newBaseQueryBuilder());
        // newEloquentBuilder() was added in 4.1
        $builder = $this->newEloquentBuilder($this->newBaseQueryBuilder());

        // Once Firefly has the query builders, it will set the model instances so the
        // builder can easily access any information it may need from the model
        // while it is constructing and executing various queries against it.
        $builder->setModel($this)->with($this->with);

        if ($excludeDeleted && $this->softDelete) {
            $builder->whereNull($this->getQualifiedDeletedAtColumn());
        }

        if ($this->subclassField && $this->isSubclass()) {
            $builder->where($this->subclassField, '=', get_class($this));
        }

        return $builder;
    }

    /**
     * ensure that the subclass field is assigned on save
     *
     * @param array    $rules
     * @param array    $customMessages
     * @param array    $options
     * @param callable $beforeSave
     * @param callable $afterSave
     *
     * @return bool
     */
    public function save(array $rules = [], array $customMessages = [], array $options = [], \Closure $beforeSave = null, \Closure $afterSave = null)
    {
        if ($this->subclassField) {
            $this->attributes[$this->subclassField] = get_class($this);
        }

        return parent::save($rules, $customMessages, $options, $beforeSave, $afterSave);
    }

    /**
     * if no subclass is defined, function as normal
     *
     * @param array $attributes
     *
     * @return \Illuminate\Database\Eloquent\Model|static
     */
    public function mapData(array $attributes)
    {
        if (!$this->subclassField) {
            return $this->newInstance();
        }

        return new $attributes[$this->subclassField];
    }

    /**
     * @return bool
     */
    public function isSubclass()
    {
        return $this->isSubclass;
    }
} 