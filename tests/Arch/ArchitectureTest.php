<?php

// Reference data and value objects only: no HTTP, no routing, no screens.
// A host draws its own pickers from the models.

arch('the package never uses HTTP, routing or Inertia')
    ->expect('Apex\Locale')
    ->not->toUse(['Illuminate\Http', 'Illuminate\Routing', 'Inertia']);

arch('no debugging calls are left behind')
    ->expect(['dd', 'dump', 'ray', 'var_dump'])
    ->not->toBeUsed();
