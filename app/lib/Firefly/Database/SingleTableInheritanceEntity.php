<?php



namespace Firefly\Database;


abstract class SingleTableInheritanceEntity extends \LaravelBook\Ardent\Ardent
{
    /**
     * The field that stores the subclass
     *
     * @var string
     */
    protected $subclassField = null;
    /**
     * must be overridden and set to true in subclasses
     *
     * @var bool
     */
    protected $isSubclass = false;

    public function newFromBuilder($attributes = array())
    {
        $instance = $this->mapData((array)$attributes)->newInstance(array(), true);
        $instance->setRawAttributes((array)$attributes, true);
        return $instance;
    }

    // if no subclass is defined, function as normal

    public function mapData(array $attributes)
    {
        if (!$this->subclassField) {
            return $this->newInstance();
        }

        return new $attributes[$this->subclassField];
    }

    // instead of using $this->newInstance(), call
    // newInstance() on the object from mapData

    public function newQuery($excludeDeleted = true)
    {
        // If using Laravel 4.0.x then use the following commented version of this command
        // $builder = new Builder($this->newBaseQueryBuilder());
        // newEloquentBuilder() was added in 4.1
        $builder = $this->newEloquentBuilder($this->newBaseQueryBuilder());

        // Once we have the query builders, we will set the model instances so the
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

    public function isSubclass()
    {
        return $this->isSubclass;
    }

    // ensure that the subclass field is assigned on save
    public function save(
        array $rules = array(),
        array $customMessages = array(),
        array $options = array(),
        \Closure $beforeSave = null,
        \Closure $afterSave = null
    ) {
        if ($this->subclassField) {
            $this->attributes[$this->subclassField] = get_class($this);
        }
        return parent::save($rules, $customMessages, $options, $beforeSave, $afterSave);
    }
} 