<?php
return [
    'index_periods'          => '1D', '1W', '1M', '3M', '6M', 'custom',
    'budget_periods'         => 'daily', 'weekly', 'monthly', 'quarterly', 'half-year', 'yearly',
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
    'range_to_repeat_freq'   => [
        '1D'     => 'weekly',
        '1W'     => 'weekly',
        '1M'     => 'monthly',
        '3M'     => 'quarterly',
        '6M'     => 'half-year',
        'custom' => 'monthly'
    ],

    'date_formats_by_period' => [
        'monthly' => [
            'group_date'   => 'Y-m',
            'display_date' => 'F Y'
        ],
        'weekly'  => [
            'group_date'   => 'Y-W',
            'display_date' => '\W\e\e\k W, Y'
        ]
    ]
];