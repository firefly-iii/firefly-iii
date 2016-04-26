<?php
declare(strict_types = 1);
namespace FireflyIII\Helpers\Csv;

use Auth;
use Crypt;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Csv\Mapper\MapperInterface;
use League\Csv\Reader;
use Log;
use ReflectionException;
use Session;
use SplFileObject;
use Storage;

/**
 * Class Wizard
 *
 * @package FireflyIII\Helpers\Csv
 */
class Wizard implements WizardInterface
{

    /**
     * @param Reader $reader
     * @param array  $map
     * @param bool   $hasHeaders
     *
     * @return array
     */
    public function getMappableValues(Reader $reader, array $map, bool $hasHeaders): array
    {
        $values = [];
        /*
         * Loop over the CSV and collect mappable data:
         */
        $keys = array_keys($map);
        foreach ($reader as $index => $row) {
            if ($this->useRow($hasHeaders, $index)) {
                // collect all map values

                foreach ($keys as $column) {
                    $values[$column][] = $row[$column];
                }
            }
        }
        /*
         * Make each one unique.
         */
        $values = $this->uniqueRecursive($values);

        return $values;
    }

    /**
     * @param array $roles
     * @param array $map
     *
     * @return array
     */
    public function processSelectedMapping(array $roles, array $map): array
    {
        $configRoles = config('csv.roles');
        $maps        = [];


        if (is_array($map)) {
            $keys = array_keys($map);
            foreach ($keys as $index) {
                if (isset($roles[$index])) {
                    $name = $roles[$index];
                    if ($configRoles[$name]['mappable']) {
                        $maps[$index] = $name;
                    }
                }
            }
        }

        return $maps;

    }

    /**
     * @param array $input
     *
     * @return array
     */
    public function processSelectedRoles(array $input): array
    {
        $roles = [];


        /*
         * Store all rows for each column:
         */
        if (is_array($input)) {
            foreach ($input as $index => $role) {
                if ($role != '_ignore') {
                    $roles[$index] = $role;
                }
            }
        }

        return $roles;
    }

    /**
     * @param array $fields
     *
     * @return bool
     */
    public function sessionHasValues(array $fields): bool
    {
        foreach ($fields as $field) {
            if (!Session::has($field)) {
                Log::error('Session is missing field: ' . $field);

                return false;
            }
        }

        return true;
    }

    /**
     * @param array $map
     *
     * @return array
     * @throws FireflyException
     */
    public function showOptions(array $map): array
    {
        $options = [];
        foreach ($map as $index => $columnRole) {

            $mapper = config('csv.roles.' . $columnRole . '.mapper');
            if (is_null($mapper)) {
                throw new FireflyException('Cannot map field of type "' . $columnRole . '".');
            }
            $class = 'FireflyIII\Helpers\Csv\Mapper\\' . $mapper;
            try {
                /** @var MapperInterface $mapObject */
                $mapObject = app($class);
            } catch (ReflectionException $e) {
                throw new FireflyException('Column "' . $columnRole . '" cannot be mapped because mapper class ' . $mapper . ' does not exist.');
            }
            $set             = $mapObject->getMap();
            $options[$index] = $set;
        }

        return $options;
    }

    /**
     * @param string $path
     *
     * @return string
     */
    public function storeCsvFile(string $path): string
    {
        $time             = str_replace(' ', '-', microtime());
        $fileName         = 'csv-upload-' . Auth::user()->id . '-' . $time . '.csv.encrypted';
        $disk             = Storage::disk('upload');
        $file             = new SplFileObject($path, 'r');
        $content          = $file->fread($file->getSize());
        $contentEncrypted = Crypt::encrypt($content);
        $disk->put($fileName, $contentEncrypted);

        return $fileName;


    }

    /**
     * @param array $array
     *
     * @return array
     */
    protected function uniqueRecursive(array $array)
    {
        foreach ($array as $column => $found) {
            $array[$column] = array_unique($found);
        }

        return $array;
    }

    /**
     * @param bool $hasHeaders
     * @param int  $index
     *
     * @return bool
     */
    protected function useRow(bool $hasHeaders, int $index)
    {
        return ($hasHeaders && $index > 1) || !$hasHeaders;
    }
}
