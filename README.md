# Apex Locale

Shared locale reference for Laravel: countries, currencies, `Money`, VAT bands with dated rates,
and a resolver. Installed by Apex TBX and Apex Till and usable by any Exor product. Proprietary,
EXOR Group.

> **Status: 0.x, scaffold (phase LC1).**

## Install

```bash
composer require exorgroup/apex-locale
```

The provider is discovered automatically. Publish the config if you need to change it:

```bash
php artisan vendor:publish --tag=locale-config
```

## Layout

| | |
|---|---|
| `src/Models` | Reference data. Every model extends `Apex\Locale\Models\Model` |
| `config/locale.php` | Configuration, merged under `locale` |
| `database/migrations` | Migrations; every table is prefixed `loc_` |

`Apex\Locale\Models\Model` gives every table the `loc_` prefix (`Country` → `loc_countries`) and
makes every model soft-delete. The package ships no HTTP, routing or screens; the architecture
test enforces that.

## Tests

```bash
composer test
```

Pest on Orchestra Testbench, with SQLite in memory. Suites: `Feature` and `Arch`.

## Planning

Decisions live in the host project that drives development (`apex-tbx`:
`.claude/apextill/decisions.md` and `.claude/apextill/phases/LC1/LC1-tasks.md`).
