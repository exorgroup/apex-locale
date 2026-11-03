<?php

use Apex\Locale\LocaleServiceProvider;

it('registers the service provider', function () {
    expect(app()->getProvider(LocaleServiceProvider::class))->toBeInstanceOf(LocaleServiceProvider::class);
});

it('merges the package config under locale', function () {
    expect(config('locale'))->toBeArray();
});
