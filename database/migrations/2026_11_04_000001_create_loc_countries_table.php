<?php

use Apex\Locale\Seeders\CountrySeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `loc_countries` (LC1/002), moved from TBX's `countries` (AT/002).
 *
 * Everything that references a country stores the ISO-2 CODE, never this row's id, so a reseed or a
 * restore can never re-point a reference.
 *
 * `iso2` gets a PLAIN index, not a unique one: a unique index counts soft-deleted rows, so a removed
 * country would reserve its code for ever. Uniqueness among live rows is kept by the seeder
 * (insert-if-missing) and by any form's `deleted_at,NULL` rule.
 *
 * `cash_rounding` is the step that CASH totals round to here, in the minor units of the currency
 * cashed. NULL means the currency's own default applies (LC1 D3).
 *
 * A host that already has the table (TBX, which renames its own `countries` table) is left alone.
 * Multi-tenancy: landlord data (LC1 D9).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('loc_countries')) {
            Schema::create('loc_countries', function (Blueprint $table) {
                $table->id();
                $table->char('iso2', 2)->index();
                $table->char('iso3', 3);
                $table->string('name', 120);
                $table->unsignedSmallInteger('sort_order')->default(100);
                $table->unsignedSmallInteger('cash_rounding')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        (new CountrySeeder)->run();
    }

    public function down(): void
    {
        Schema::dropIfExists('loc_countries');
    }
};
