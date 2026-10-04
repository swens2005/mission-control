<?php

use Illuminate\Support\Facades\DB;

// Production's MariaDB creates MyISAM tables by default (1000-byte key limit,
// no transactions or foreign keys). CI sets the same default, so this proves
// every migrated table is InnoDB anyway. See ADR 0002.
test('every table uses the InnoDB engine', function () {
    $tables = DB::select(
        'select table_name as name, engine from information_schema.tables where table_schema = database()'
    );

    expect($tables)->not->toBeEmpty();

    foreach ($tables as $table) {
        expect($table->engine)->toBe('InnoDB', "{$table->name} is {$table->engine}");
    }
})->skip(fn () => ! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true), 'Only meaningful on MySQL/MariaDB');
