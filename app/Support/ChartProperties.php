<?php

namespace FireflyIII\Support;


use Auth;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Preferences as Prefs;

/**
 * Class ChartProperties
 *
 * @package FireflyIII\Support
 */
class ChartProperties
{

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
     * @return string
     */
    public function md5()
    {
        $string = '';
        //Log::debug('--- building string ---');
        foreach ($this->properties as $property) {

            if ($property instanceof Collection || $property instanceof EloquentCollection) {
                $string .= print_r($property->toArray(), true);
                continue;
            }
            if ($property instanceof Carbon) {
                $string .= $property->toRfc3339String();
                continue;
            }
            if (is_object($property)) {
                $string .= $property->__toString();
            }
            if (is_array($property)) {
                $string .= print_r($property, true);
            }
            $string .= (string)$property;
        }

        return md5($string);
    }
}