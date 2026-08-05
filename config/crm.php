<?php

$tablePrefix = (string) env('CRM_TABLE_PREFIX', 'crm_');

return [
    'route_prefix' => env('CRM_ROUTE_PREFIX', 'crm'),
    'route_middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Database tables
    |--------------------------------------------------------------------------
    |
    | Set CRM_TABLE_PREFIX=crm_ before running migrations to create tables such
    | as crm_companies and crm_statuses. Individual table names may also be
    | overridden when integrating the package into an existing application.
    |
    */
    'table_prefix' => $tablePrefix,

    'tables' => [
        'companies' => env('CRM_TABLE_COMPANIES', $tablePrefix.'companies'),
        'statuses' => env('CRM_TABLE_STATUSES', $tablePrefix.'statuses'),
        'relationships' => env('CRM_TABLE_RELATIONSHIPS', $tablePrefix.'relationships'),
        'notes' => env('CRM_TABLE_NOTES', $tablePrefix.'notes'),
        'phone_calls' => env('CRM_TABLE_PHONE_CALLS', $tablePrefix.'phone_calls'),
        'saved_searches' => env('CRM_TABLE_SAVED_SEARCHES', $tablePrefix.'saved_searches'),
        'google_places_usage_logs' => env(
            'CRM_TABLE_GOOGLE_PLACES_USAGE_LOGS',
            $tablePrefix.'google_places_usage_logs'
        ),
        'business_profiles' => env('CRM_TABLE_BUSINESS_PROFILES', $tablePrefix.'business_profiles'),
        'tags' => env('CRM_TABLE_TAGS', $tablePrefix.'tags'),
        'taggables' => env('CRM_TABLE_TAGGABLES', $tablePrefix.'taggables'),
    ],
];
