<?php

namespace FireflyIII\Shared\Json;

/**
 * Class Json
 * @package FireflyIII\Shared\Json
 */
class Json
{
    /**
     * Grabs all the parameters entered by the DataTables JQuery plugin and creates
     * a nice array to be used by the other methods. It's also cleaning up and what-not.
     *
     * @return array
     */
    public function dataTableParameters()
    {
        /*
         * Process all parameters!
         */
        if (intval(\Input::get('length')) < 0) {
            $length = 10000; // we get them all if no length is defined.
        } else {
            $length = intval(\Input::get('length'));
        }
        $parameters = [
            'start' => intval(\Input::get('start')),
            'length' => $length,
            'draw' => intval(\Input::get('draw')),
        ];


        /*
         * Columns:
         */
        if (!is_null(\Input::get('columns')) && is_array(\Input::get('columns'))) {
            foreach (\Input::get('columns') as $column) {
                $parameters['columns'][] = [
                    'data' => $column['data'],
                    'name' => $column['name'],
                    'searchable' => $column['searchable'] == 'true' ? true : false,
                    'orderable' => $column['orderable'] == 'true' ? true : false,
                    'search' => [
                        'value' => $column['search']['value'],
                        'regex' => $column['search']['regex'] == 'true' ? true : false,
                    ]
                ];
            }
        }


        /*
         * Sorting.
         */
        $parameters['orderOnAccount'] = false;
        if (!is_null(\Input::get('order')) && is_array(\Input::get('order'))) {
            foreach (\Input::get('order') as $order) {
                $columnIndex           = intval($order['column']);
                $columnName            = $parameters['columns'][$columnIndex]['name'];
                $parameters['order'][] = [
                    'name' => $columnName,
                    'dir' => strtoupper($order['dir'])
                ];
                if ($columnName == 'to' || $columnName == 'from') {
                    $parameters['orderOnAccount'] = true;
                }
            }
        }
        /*
         * Search parameters:
         */
        $parameters['search'] = [
            'value' => '',
            'regex' => false
        ];
        if (!is_null(\Input::get('search')) && is_array(\Input::get('search'))) {
            $search               = \Input::get('search');
            $parameters['search'] = [
                'value' => $search['value'],
                'regex' => $search['regex'] == 'true' ? true : false
            ];
        }
        return $parameters;
    }
} 