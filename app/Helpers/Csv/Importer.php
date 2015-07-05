<?php

namespace FireflyIII\Helpers\Csv;

use App;
use Carbon\Carbon;
use Config;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Csv\Converter\ConverterInterface;

/**
 * Class Importer
 *
 * @package FireflyIII\Helpers\Csv
 */
class Importer
{

    /** @var Data */
    protected $data;

    /** @var array */
    protected $map;

    /** @var  array */
    protected $mapped;

    /** @var  array */
    protected $roles;

    /**
     * @param $value
     */
    public function parseRaboDebetCredit($value)
    {
        if ($value == 'D') {
            return -1;
        }

        return 1;
    }

    /**
     *
     */
    public function run()
    {
        $this->map    = $this->data->getMap();
        $this->roles  = $this->data->getRoles();
        $this->mapped = $this->data->getMapped();
        foreach ($this->data->getReader() as $row) {
            $this->importRow($row);
        }
    }

    /**
     * @param $row
     *
     * @throws FireflyException
     */
    protected function importRow($row)
    {
        /*
         * These fields are necessary to create a new transaction journal. Some are optional:
         */
        $data  = $this->getFiller();
        foreach ($row as $index => $value) {
            $role  = isset($this->roles[$index]) ? $this->roles[$index] : '_ignore';
            $class = Config::get('csv.roles.' . $role . '.converter');
            $field = Config::get('csv.roles.' . $role . '.field');

            if (is_null($class)) {
                throw new FireflyException('No converter for field of type "' . $role . '".');
            }
            if (is_null($field)) {
                throw new FireflyException('No place to store value of type "' . $role . '".');
            }
            /** @var ConverterInterface $converter */
            $converter = App::make('FireflyIII\Helpers\Csv\Converter\\' . $class);
            $converter->setData($data); // the complete array so far.
            $converter->setIndex($index);
            $converter->setValue($value);
            $converter->setRole($role);
            //            if (is_array($field)) {
            //                $convertResult = $converter->convert();
            //                foreach ($field as $fieldName) {
            //                    $data[$fieldName] = $convertResult[$fieldName];
            //                }
            //            } else {
            $data[$field] = $converter->convert();
            //            }


            //                case 'description':
            //                    $data['description'] .= ' ' . $value;
            //                    break;
            //                case '_ignore':
            //                     ignore! (duh)
            //                    break;
            //                case 'account-iban':
            //                    $data['asset-account'] = $this->findAssetAccount($index, $value);
            //                    break;
            //                case 'currency-code':
            //                    $data['currency'] = $this->findCurrency($index, $value, $role);
            //                    break;
            //                case 'date-transaction':
            //                    $data['date'] = $this->parseDate($value);
            //                    break;
            //                case 'rabo-debet-credit':
            //                    $data['amount-modifier'] = $this->parseRaboDebetCredit($value);
            //                    break;
            //                default:
            //                    throw new FireflyException('Cannot process row of type "' . $role . '".');
            //                    break;


        }
        $data = $this->postProcess($data);
        var_dump($data);



        exit;

    }

    /**
     * @return array
     */
    protected function getFiller()
    {
        return [
            'description'     => '',
            'asset-account'   => null,
            'date'            => null,
            'currency'        => null,
            'amount'          => null,
            'amount-modifier' => 1,
            'ignored'         => null,
        ];

    }

    /**
     * @param array $data
     *
     * @return array
     */
    protected function postProcess(array $data)
    {
        $data['description'] = trim($data['description']);


        return $data;
    }

    /**
     * @param Data $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @param $value
     *
     * @return Carbon
     */
    protected function parseDate($value)
    {
        return Carbon::createFromFormat($this->data->getDateFormat(), $value);
    }

}