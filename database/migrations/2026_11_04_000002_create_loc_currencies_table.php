<?php

use Apex\Locale\Seeders\CurrencySeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `loc_currencies`: ISO 4217 (LC1/003).
 *
 * Money-bearing records store the alphabetic `code` (`EUR`), never this row's id (P-D41).
 * `code` gets a PLAIN index, for the soft-delete reason given on `loc_countries`.
 *
 * `decimals` is the minor unit: 2 for EUR (cents), 0 for JPY, 3 for KWD. Amounts are integers in
 * that unit. `cash_rounding` is the default step for cash totals, in minor units; a country may
 * override it (LC1 D3).
 *
 * Multi-tenancy: landlord data (LC1 D9).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loc_currencies', function (Blueprint $table) {
            $table->id();
            $table->char('code', 3)->index();
            $table->char('numeric_code', 3);
            $table->string('name', 80);
            $table->unsignedTinyInteger('decimals');
            $table->unsignedSmallInteger('cash_rounding')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });

        (new CurrencySeeder)->run();
    }

    public function down(): void
    {
        Schema::dropIfExists('loc_currencies');
    }
};
