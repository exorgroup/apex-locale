<?php

use Apex\Locale\Seeders\VatBandSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * VAT (LC1/005, D4–D6).
 *
 * `loc_vat_bands`: one country's rate for one band over a period, in basis points (1800 = 18.00 %).
 * NULL `effective_from` = since before the records; NULL `effective_to` = until further notice.
 * Landlord data (D9).
 *
 * `loc_vat_categories`: tenant-named categories, each pointing at a band. Tenant data (D9).
 * `name` gets a plain index: uniqueness among live rows belongs to the form's `deleted_at,NULL`
 * rule, never to a unique index (soft deletes).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loc_vat_bands', function (Blueprint $table) {
            $table->id();
            $table->char('country_code', 2);
            $table->string('band', 20);
            $table->string('label', 60);
            $table->unsignedInteger('basis_points');
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['country_code', 'band']);
        });

        Schema::create('loc_vat_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80)->index();
            $table->string('band', 20);
            $table->timestamps();
            $table->softDeletes();
        });

        (new VatBandSeeder)->run();
    }

    public function down(): void
    {
        Schema::dropIfExists('loc_vat_categories');
        Schema::dropIfExists('loc_vat_bands');
    }
};
