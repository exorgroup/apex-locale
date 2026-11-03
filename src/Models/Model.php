<?php

namespace Apex\Locale\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Base for every Locale model.
 *
 * - Tables carry the `loc_` prefix: `Country` → `loc_countries`. A model that names its
 *   own `$table` gets the prefix too, unless it already starts with `loc_`.
 * - Every model soft-deletes (no hard deletes, anywhere).
 */
abstract class Model extends Eloquent
{
    use SoftDeletes;

    public const TABLE_PREFIX = 'loc_';

    public function getTable(): string
    {
        $table = parent::getTable();

        return str_starts_with($table, self::TABLE_PREFIX) ? $table : self::TABLE_PREFIX.$table;
    }
}
