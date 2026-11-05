<?php

use Illuminate\Support\Facades\Schema;

// MySQL refuses an identifier longer than 64 characters; SQLite, which this suite runs on, does not.
// An exchange-rate index named 66 characters by Laravel passed here and failed on TBX's MySQL
// (LC1/007). Every table and index this package creates is checked here instead.

beforeEach(function () {
    $this->artisan('migrate')->run();
});

it('keeps every table and index name within MySQL\'s 64 characters', function () {
    $tables = collect(Schema::getTables())->pluck('name')->filter(fn ($name) => str_starts_with($name, 'loc_'));

    expect($tables)->not->toBeEmpty();

    foreach ($tables as $table) {
        expect(strlen($table))->toBeLessThanOrEqual(64, "table {$table}");

        foreach (Schema::getIndexes($table) as $index) {
            expect(strlen($index['name']))->toBeLessThanOrEqual(64, "index {$index['name']} on {$table}");
        }
    }
});
