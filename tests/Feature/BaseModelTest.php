<?php

use Apex\Locale\Tests\Fixtures\Widget;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::create('loc_widgets', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->timestamps();
        $table->softDeletes();
    });
});

it('prefixes the derived table name with loc_', function () {
    expect((new Widget)->getTable())->toBe('loc_widgets');
});

it('prefixes an explicit table name that lacks loc_', function () {
    expect((new Widget)->setTable('gadgets')->getTable())->toBe('loc_gadgets');
});

it('does not double an explicit loc_ prefix', function () {
    expect((new Widget)->setTable('loc_gadgets')->getTable())->toBe('loc_gadgets');
});

it('soft-deletes: the row stays, hidden from queries', function () {
    $widget = Widget::create(['name' => 'Cup']);

    $widget->delete();

    expect(Widget::count())->toBe(0)
        ->and(Widget::withTrashed()->count())->toBe(1)
        ->and(Widget::withTrashed()->first()->deleted_at)->not->toBeNull();
});
