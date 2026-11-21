<?php

namespace Tests\Traits;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Schema;

/**
 * Every test runs on SQLite, and SQLite's double-quote handling hides a typo
 * that MariaDB would throw on. A query for a column that does not exist:
 *
 *  - with an unquoted identifier, "SELECT stock_quantity FROM products",
 *    throws "no such column", so it cannot go unnoticed;
 *  - with a quoted identifier, which is what Laravel's SQLite grammar emits
 *    for ->where('stock_quantity', ...) and ->get(['stock_quantity']),
 *    SQLite treats an unknown double-quoted name as a string literal. The
 *    comparison becomes "0 < 'stock_quantity'", the select list returns the
 *    literal text, zero rows match, and the assertion passes.
 *
 * Production runs MariaDB, where the same typo is a hard unknown-column
 * error. So on SQLite a mistyped column silently turns an invariant into
 * "there is nothing to check", and the test reports green forever.
 *
 * assertQueryReadsExistingColumns() reads the SQL Laravel actually built and
 * checks every identifier in it against the schema, so a typo in the query is
 * caught without anyone having to remember to update a list. The list-based
 * assertColumnsExist() exists for the columns written by hand inside a raw
 * fragment, which the parser can only see when they are table-qualified.
 */
trait AssertsSchemaColumns
{
    /**
     * Check every identifier of a query Laravel has already built.
     *
     * Two rules, because Laravel splits a reference into two quoted tokens
     * ("sales"."id") while a raw fragment usually writes it as one unquoted
     * token (sales.id):
     *
     *  1. every quoted token must be one of the tables in the query or a
     *     column of one of them;
     *  2. every unquoted table.column must be a real column of that table.
     *
     * Bound values never reach the SQL, they become "?" placeholders, so a
     * string literal cannot be mistaken for an identifier.
     */
    protected function assertQueryReadsExistingColumns(Builder $query): void
    {
        $sql = $query->toSql();

        preg_match_all('/\b(?:from|join)\s+[`"]([^`"]+)[`"]/i', $sql, $tableMatches);
        $tables = array_values(array_unique($tableMatches[1]));

        $this->assertNotEmpty(
            $tables,
            "Schema guard: no table found in [{$sql}], so no column in it could be checked. "
            .'Extend the guard for this query shape.'
        );

        foreach ($tables as $table) {
            $this->assertTrue(
                Schema::hasTable($table),
                "Schema guard: table [{$table}] does not exist, so the invariant reading it checks nothing."
            );
        }

        $columnsByTable = [];
        $everyColumn = [];
        foreach ($tables as $table) {
            $columnsByTable[$table] = Schema::getColumnListing($table);
            foreach ($columnsByTable[$table] as $column) {
                $everyColumn[$column] = true;
            }
        }

        $missing = [];

        preg_match_all('/[`"]([^`"]+)[`"]/', $sql, $identifierMatches);
        foreach (array_unique($identifierMatches[1]) as $identifier) {
            if (in_array($identifier, $tables, true)) {
                continue; // the table names themselves, in FROM and JOIN
            }
            if (! isset($everyColumn[$identifier])) {
                $missing[] = $identifier;
            }
        }

        preg_match_all('/\b([A-Za-z_][A-Za-z0-9_]*)\s*\.\s*([A-Za-z_][A-Za-z0-9_]*)\b/', $sql, $qualified);
        foreach ($qualified[1] as $position => $table) {
            if (! isset($columnsByTable[$table])) {
                continue; // not a table of this query, so not a qualified column either
            }
            if (! in_array($qualified[2][$position], $columnsByTable[$table], true)) {
                $missing[] = $table.'.'.$qualified[2][$position];
            }
        }

        $missing = array_values(array_unique($missing));

        $this->assertSame(
            [],
            $missing,
            'Schema guard: ['.implode(', ', $tables).'] has no '.implode(', ', $missing)
            .'. The query would silently match nothing on SQLite and fail as an unknown column in production.'
        );
    }

    /**
     * Declare the columns a hand-written fragment reads, for the ones the
     * parser cannot see because they are not table-qualified.
     *
     * @param  array<int, string>  $columns
     */
    protected function assertColumnsExist(string $table, array $columns): void
    {
        $this->assertTrue(
            Schema::hasTable($table),
            "Schema guard: table [{$table}] does not exist, so the invariant that reads it checks nothing."
        );

        $missing = array_values(array_diff($columns, Schema::getColumnListing($table)));

        $this->assertSame(
            [],
            $missing,
            "Schema guard: [{$table}] has no ".implode(', ', $missing)
            .'. The query reading it would silently match nothing on SQLite and explode in production.'
        );
    }
}
