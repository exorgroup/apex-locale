<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `loc_exchange_rates`: dated FX, EMPTY until a second currency exists (P-D41, LC1 D8).
 *
 * One unit of `base_currency` is worth `rate` units of `quote_currency` on `effective_on`. The rate is
 * an exact DECIMAL, never a float; nothing converts with it yet (LC1: conversion is not in it).
 * Codes, not ids. Landlord data (D9).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loc_exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->char('base_currency', 3);
            $table->char('quote_currency', 3);
            $table->decimal('rate', 20, 10);
            $table->date('effective_on');
            $table->timestamps();
            $table->softDeletes();

            // Named: the generated name is 66 characters and MySQL allows 64 (LC1/007).
            $table->index(['base_currency', 'quote_currency', 'effective_on'], 'loc_fx_pair_date_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loc_exchange_rates');
    }
};
