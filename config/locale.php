<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default country
    |--------------------------------------------------------------------------
    |
    | ISO-2 code, sorted first in country choosers (`Country::options()`).
    | Read when the countries are first seeded. Changing it later does not
    | re-sort rows already there, because the seeder never overwrites.
    | Null means plain alphabetical order.
    |
    */

    'default_country' => null,

    /*
    |--------------------------------------------------------------------------
    | Reporting currency
    |--------------------------------------------------------------------------
    |
    | ISO 4217 code that reports total in (P-D41, LC1 D8). EUR only at go-live:
    | every money-bearing record still carries its own `currency`, and nothing
    | converts yet. Becomes a tenant setting under multi-tenancy.
    |
    */

    'reporting_currency' => 'EUR',

];
