<?php

return [

    // this is not about security per se, but about making this string less obvious to bots
    // although they could simply parse the value lol
    'dsn'         => base64_decode(strrev('=IzLnJ3bukWap1SesZWZylmZuknc05WZzB0NmRDNhFWM1EzNlNWY3IWOkRzN0EjYygDM0UzMhBjYi9yL6MHc0RHa')),

    // capture release as git sha
    // 'release' => trim(exec('git --git-dir ' . base_path('.git') . ' log --pretty="%h" -n1 HEAD')),

    // When left empty or `null` the Laravel environment will be used
    'environment' => env('SENTRY_ENVIRONMENT'),

    'breadcrumbs' => [
        // Capture Laravel logs in breadcrumbs
        'logs'         => true,

        // Capture SQL queries in breadcrumbs
        'sql_queries'  => true,

        // Capture bindings on SQL queries logged in breadcrumbs
        'sql_bindings' => true,

        // Capture queue job information in breadcrumbs
        'queue_info'   => true,

        // Capture command information in breadcrumbs
        'command_info' => true,
    ],

    'tracing'          => [
        // Trace queue jobs as their own transactions
        'queue_job_transactions' => env('SENTRY_TRACE_QUEUE_ENABLED', false),

        // Capture queue jobs as spans when executed on the sync driver
        'queue_jobs'             => true,

        // Capture SQL queries as spans
        'sql_queries'            => true,

        // Try to find out where the SQL query originated from and add it to the query spans
        'sql_origin'             => true,

        // Capture views as spans
        'views'                  => true,

        // Indicates if the tracing integrations supplied by Sentry should be loaded
        'default_integrations'   => true,
    ],

    // @see: https://docs.sentry.io/platforms/php/configuration/options/#send-default-pii
    'send_default_pii' => false,

    'traces_sample_rate' => (float)(env('SENTRY_TRACES_SAMPLE_RATE', 0.0)),

    'controllers_base_namespace' => env('SENTRY_CONTROLLERS_BASE_NAMESPACE', 'App\\Http\\Controllers'),

];
