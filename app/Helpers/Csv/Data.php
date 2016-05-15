<?php
declare(strict_types = 1);
namespace FireflyIII\Helpers\Csv;

use Crypt;
use League\Csv\Reader;
use Session;
use Storage;

/**
 * Class Data
 *
 * @package FireflyIII\Helpers\Csv
 */
class Data
{

    /** @var string */
    protected $csvFileContent = '';
    /** @var string */
    protected $csvFileLocation = '';
    /** @var  string */
    protected $dateFormat = '';
    /** @var  string */
    protected $delimiter = '';
    /** @var  bool */
    protected $hasHeaders;
    /** @var int */
    protected $importAccount = 0;
    /** @var  array */
    protected $map = [];
    /** @var  array */
    protected $mapped = [];
    /** @var  Reader */
    protected $reader;
    /** @var  array */
    protected $roles = [];
    /** @var  array */
    protected $specifix = [];

    /**
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
        $this->sessionImportAccount();
        $this->sessionDelimiter();
    }

    /**
     *
     * @return string
     */
    public function getCsvFileContent(): string
    {
        return $this->csvFileContent ?? '';
    }

    /**
     *
     * @param string $csvFileContent
     */
    public function setCsvFileContent(string $csvFileContent)
    {
        $this->csvFileContent = $csvFileContent;
    }

    /**
     * FIXxME may return null
     *
     * @return string
     */
    public function getCsvFileLocation(): string
    {
        return $this->csvFileLocation;
    }

    /**
     *
     * @param string $csvFileLocation
     */
    public function setCsvFileLocation(string $csvFileLocation)
    {
        Session::put('csv-file', $csvFileLocation);
        $this->csvFileLocation = $csvFileLocation;
    }

    /**
     * FIXxME may return null
     *
     * @return string
     */
    public function getDateFormat(): string
    {
        return $this->dateFormat;
    }

    /**
     *
     * @param string $dateFormat
     */
    public function setDateFormat(string $dateFormat)
    {
        Session::put('csv-date-format', $dateFormat);
        $this->dateFormat = $dateFormat;
    }

    /**
     * FIXxME may return null
     *
     * @return string
     */
    public function getDelimiter(): string
    {
        return $this->delimiter;
    }

    /**
     *
     * @param string $delimiter
     */
    public function setDelimiter(string $delimiter)
    {
        Session::put('csv-delimiter', $delimiter);
        $this->delimiter = $delimiter;
    }

    /**
     *
     * @return array
     */
    public function getMap(): array
    {
        return $this->map;
    }

    /**
     *
     * @param array $map
     */
    public function setMap(array $map)
    {
        Session::put('csv-map', $map);
        $this->map = $map;
    }

    /**
     *
     * @return array
     */
    public function getMapped(): array
    {
        return $this->mapped;
    }

    /**
     *
     * @param array $mapped
     */
    public function setMapped(array $mapped)
    {
        Session::put('csv-mapped', $mapped);
        $this->mapped = $mapped;
    }

    /**
     *
     * @return Reader
     */
    public function getReader(): Reader
    {
        if (!is_null($this->csvFileContent) && strlen($this->csvFileContent) === 0) {
            $this->loadCsvFile();
        }

        if (is_null($this->reader)) {
            $this->reader = Reader::createFromString($this->getCsvFileContent());
            $this->reader->setDelimiter($this->delimiter);
        }

        return $this->reader;
    }

    /**
     *
     * @return array
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     *
     * @param array $roles
     */
    public function setRoles(array $roles)
    {
        Session::put('csv-roles', $roles);
        $this->roles = $roles;
    }

    /**
     *
     * @return array
     */
    public function getSpecifix(): array
    {
        return is_array($this->specifix) ? $this->specifix : [];
    }

    /**
     *
     * @param array $specifix
     */
    public function setSpecifix(array $specifix)
    {
        Session::put('csv-specifix', $specifix);
        $this->specifix = $specifix;
    }

    /**
     *
     * @return bool
     */
    public function hasHeaders(): bool
    {
        return $this->hasHeaders;
    }

    /**
     *
     * @param bool $hasHeaders
     */
    public function setHasHeaders(bool $hasHeaders)
    {
        Session::put('csv-has-headers', $hasHeaders);
        $this->hasHeaders = $hasHeaders;
    }

    /**
     *
     * @param int $importAccount
     */
    public function setImportAccount(int $importAccount)
    {
        Session::put('csv-import-account', $importAccount);
        $this->importAccount = $importAccount;
    }

    protected function loadCsvFile()
    {
        $file             = $this->getCsvFileLocation();
        $disk             = Storage::disk('upload');
        $content          = $disk->get($file);
        $contentDecrypted = Crypt::decrypt($content);
        $this->setCsvFileContent($contentDecrypted);
    }

    protected function sessionCsvFileLocation()
    {
        if (Session::has('csv-file')) {
            $this->csvFileLocation = (string)session('csv-file');
        }
    }

    protected function sessionDateFormat()
    {
        if (Session::has('csv-date-format')) {
            $this->dateFormat = (string)session('csv-date-format');
        }
    }

    protected function sessionDelimiter()
    {
        if (Session::has('csv-delimiter')) {
            $this->delimiter = session('csv-delimiter');
        }
    }

    protected function sessionHasHeaders()
    {
        if (Session::has('csv-has-headers')) {
            $this->hasHeaders = (bool)session('csv-has-headers');
        }
    }

    protected function sessionImportAccount()
    {
        if (Session::has('csv-import-account')) {
            $this->importAccount = intval(session('csv-import-account'));
        }
    }

    protected function sessionMap()
    {
        if (Session::has('csv-map')) {
            $this->map = (array)session('csv-map');
        }
    }

    protected function sessionMapped()
    {
        if (Session::has('csv-mapped')) {
            $this->mapped = (array)session('csv-mapped');
        }
    }

    protected function sessionRoles()
    {
        if (Session::has('csv-roles')) {
            $this->roles = (array)session('csv-roles');
        }
    }

    protected function sessionSpecifix()
    {
        if (Session::has('csv-specifix')) {
            $this->specifix = (array)session('csv-specifix');
        }
    }
}
