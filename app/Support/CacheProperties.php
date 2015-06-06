<?php

namespace FireflyIII\Support;


use Auth;
use Cache;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Preferences as Prefs;

/**
 * Class CacheProperties
 *
 * @package FireflyIII\Support
 */
class CacheProperties
{

    /** @var  string */
    protected $md5 = '';
    /** @var Collection */
    protected $properties;

    /**
     *
     */
    public function __construct()
    {
        $this->properties = new Collection;
        $this->addProperty(Auth::user()->id);
        $this->addProperty(Prefs::lastActivity());
    }

    /**
     * @param $property
     */
    public function addProperty($property)
    {
        $this->properties->push($property);
    }

    /**
     * @return mixed
     */
    public function get()
    {
        return Cache::get($this->md5);
    }

    /**
     * @return string
     */
    public function getMd5()
    {
        return $this->md5;
    }

    /**
     * @return bool
     */
    public function has()
    {
        if (getenv('APP_ENV') == 'testing') {
            return false;
        }
        $this->md5();

        return Cache::has($this->md5);
    }

    /**
     * @return void
     */
    private function md5()
    {
        foreach ($this->properties as $property) {

            if ($property instanceof Collection || $property instanceof EloquentCollection) {
                $this->md5 .= json_encode($property->toArray());
                continue;
            }
            if ($property instanceof Carbon) {
                $this->md5 .= $property->toRfc3339String();
                continue;
            }
            if (is_object($property)) {
                $this->md5 .= $property->__toString();
            }

            $this->md5 .= json_encode($property);
        }

        $this->md5 = md5($this->md5);
    }

    /**
     * @param $data
     */
    public function store($data)
    {
        Cache::forever($this->md5, $data);
    }
}
