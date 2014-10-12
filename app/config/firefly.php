<?php
use Carbon\Carbon;

return [
    'index_periods'          => ['1D', '1W', '1M', '3M', '6M','1Y', 'custom'],
    'budget_periods'         => ['daily', 'weekly', 'monthly', 'quarterly', 'half-year', 'yearly'],
    'piggybank_periods'      => ['day', 'week', 'month', 'year'],
    'periods_to_text'        => [
        'weekly'    => 'A week',
        'monthly'   => 'A month',
        'quarterly' => 'A quarter',
        'half-year' => 'Six months',
        'yearly'    => 'A year',
    ],

    'range_to_text'          => [
        '1D'     => 'day',
        '1W'     => 'week',
        '1M'     => 'month',
        '3M'     => 'three months',
        '6M'     => 'half year',
        'custom' => '(custom)'
    ],
    'range_to_name'          => [
        '1D'     => 'one day',
        '1W'     => 'one week',
        '1M'     => 'one month',
        '3M'     => 'three months',
        '6M'     => 'six months',
        '1Y'     => 'one year',
    ],
    'range_to_repeat_freq'   => [
        '1D'     => 'weekly',
        '1W'     => 'weekly',
        '1M'     => 'monthly',
        '3M'     => 'quarterly',
        '6M'     => 'half-year',
        'custom' => 'monthly'
    ],
];