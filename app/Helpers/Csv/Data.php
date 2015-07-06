<?php

namespace FireflyIII\Helpers\Csv;

use Crypt;
use League\Csv\Reader;
use Session;

/**
 * Class Data
 *
 * @package FireflyIII\Helpers\Csv
 */
class Data
{

    /** @var string */
    protected $csvFileContent;

    /** @var string */
    protected $csvFileLocation;
    /** @var  string */
    protected $dateFormat;
    /** @var  bool */
    protected $hasHeaders;

    /** @var  array */
    protected $map;
    /** @var  array */
    protected $mapped;
    /** @var  Reader */
    protected $reader;
    /** @var  array */
    protected $roles;

    /** @var  array */
    protected $specifix;

    /**
     *
     */
    public function __construct()
    {
        $this->sessionHasHeaders();
        $this->sessionDateFormat();
        $this->sessionCsvFileLocation();
        $this->sessionMap();
        $this->sessionRoles();
        $this->sessionMapped();
        $this->sessionSpecifix();
    }

    protected function sessionHasHeaders()
    {
        if (Session::has('csv-has-headers')) {
            $this->hasHeaders = (bool)Session::get('csv-has-headers');
        }
    }

    protected function sessionDateFormat()
    {
        if (Session::has('csv-date-format')) {
            $this->dateFormat = (string)Session::get('csv-date-format');
        }
    }

    protected function sessionCsvFileLocation()
    {
        if (Session::has('csv-file')) {
            $this->csvFileLocation = (string)Session::get('csv-file');
        }
    }

    protected function sessionMap()
    {
        if (Session::has('csv-map')) {
            $this->map = (array)Session::get('csv-map');
        }
    }

    protected function sessionRoles()
    {
        if (Session::has('csv-roles')) {
            $this->roles = (array)Session::get('csv-roles');
        }
    }

    protected function sessionMapped()
    {
        if (Session::has('csv-mapped')) {
            $this->mapped = (array)Session::get('csv-mapped');
        }
    }

    protected function sessionSpecifix()
    {
        if (Session::has('csv-specifix')) {
            $this->specifix = (array)Session::get('csv-specifix');
        }
    }

    /**
     * @return string
     */
    public function getDateFormat()
    {
        return $this->dateFormat;
    }

    /**
     * @param mixed $dateFormat
     */
    public function setDateFormat($dateFormat)
    {
        Session::put('csv-date-format', $dateFormat);
        $this->dateFormat = $dateFormat;
    }

    /**
     * @return bool
     */
    public function getHasHeaders()
    {
        return $this->hasHeaders;
    }

    /**
     * @param bool $hasHeaders
     */
    public function setHasHeaders($hasHeaders)
    {
        Session::put('csv-has-headers', $hasHeaders);
        $this->hasHeaders = $hasHeaders;
    }

    /**
     * @return array
     */
    public function getMap()
    {
        return $this->map;
    }

    /**
     * @param array $map
     */
    public function setMap(array $map)
    {
        Session::put('csv-map', $map);
        $this->map = $map;
    }

    /**
     * @return array
     */
    public function getMapped()
    {
        return $this->mapped;
    }

    /**
     * @param array $mapped
     */
    public function setMapped(array $mapped)
    {
        Session::put('csv-mapped', $mapped);
        $this->mapped = $mapped;
    }

    /**
     * @return Reader
     */
    public function getReader()
    {

        if (strlen($this->csvFileContent) === 0) {
            $this->loadCsvFile();
        }

        if (is_null($this->reader)) {
            $this->reader = Reader::createFromString($this->getCsvFileContent());
        }

        return $this->reader;
    }

    protected function loadCsvFile()
    {
        $file             = $this->getCsvFileLocation();
        $content          = file_get_contents($file);
        $contentDecrypted = Crypt::decrypt($content);
        $this->setCsvFileContent($contentDecrypted);
    }

    /**
     * @return string
     */
    public function getCsvFileLocation()
    {
        return $this->csvFileLocation;
    }

    /**
     * @param string $csvFileLocation
     */
    public function setCsvFileLocation($csvFileLocation)
    {
        Session::put('csv-file', $csvFileLocation);
        $this->csvFileLocation = $csvFileLocation;
    }

    /**
     * @return string
     */
    public function getCsvFileContent()
    {
        return $this->csvFileContent;
    }

    /**
     * @param string $csvFileContent
     */
    public function setCsvFileContent($csvFileContent)
    {
        $this->csvFileContent = $csvFileContent;
    }

    /**
     * @return array
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * @param array $roles
     */
    public function setRoles(array $roles)
    {
        Session::put('csv-roles', $roles);
        $this->roles = $roles;
    }

    /**
     * @return array
     */
    public function getSpecifix()
    {
        return $this->specifix;
    }

    /**
     * @param array $specifix
     */
    public function setSpecifix($specifix)
    {
        Session::put('csv-specifix', $specifix);
        $this->specifix = $specifix;
    }


}