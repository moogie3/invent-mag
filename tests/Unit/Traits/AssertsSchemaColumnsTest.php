<?php

namespace Tests\Unit\Traits;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\AssertsSchemaColumns;

/**
 * The guard only matters if it actually fires. Each test here breaks a query
 * on purpose, in the shape SQLite would hide: an unknown double-quoted
 * identifier becomes a string literal, the query matches nothing, and the
 * assertion it was meant to protect reports green.
 *
 * If any of these start passing, the guard has stopped checking and every
 * invariant built on it is back to being vacuous.
 */
class AssertsSchemaColumnsTest extends TestCase
{
    use AssertsSchemaColumns;
    use RefreshDatabase;

    /** Run a probe and return the failure message it produced, or null. */
    private function failureFrom(callable $probe): ?string
    {
        try {
            $probe();
        } catch (AssertionFailedError $e) {
            return $e->getMessage();
        }

        return null;
    }

    #[Test]
    public function a_typo_in_a_where_column_fails_the_guard(): void
    {
        $message = $this->failureFrom(fn () => $this->assertQueryReadsExistingColumns(
            DB::table('sales')->where('invoce', '!=', '')
        ));

        $this->assertNotNull($message, 'A typo in a where column slipped through the guard.');
        $this->assertStringContainsString('invoce', $message);
    }

    #[Test]
    public function a_typo_in_a_select_column_fails_the_guard(): void
    {
        $message = $this->failureFrom(fn () => $this->assertQueryReadsExistingColumns(
            DB::table('sales')->select('total', 'ttal')
        ));

        $this->assertNotNull($message, 'A typo in a select column slipped through the guard.');
        $this->assertStringContainsString('ttal', $message);
    }

    #[Test]
    public function a_typo_in_a_join_column_fails_the_guard(): void
    {
        $message = $this->failureFrom(fn () => $this->assertQueryReadsExistingColumns(
            DB::table('sales')->join('sales_items', 'sales_items.sale_id', '=', 'sales.id')
        ));

        $this->assertNotNull($message, 'A typo in a join column slipped through the guard.');
        $this->assertStringContainsString('sale_id', $message);
    }

    #[Test]
    public function a_typo_in_a_table_qualified_raw_column_fails_the_guard(): void
    {
        $message = $this->failureFrom(fn () => $this->assertQueryReadsExistingColumns(
            DB::table('sales_items')->selectRaw('sales_items.totl as t')
        ));

        $this->assertNotNull($message, 'A typo in a raw column slipped through the guard.');
        $this->assertStringContainsString('sales_items.totl', $message);
    }

    #[Test]
    public function a_table_that_is_not_there_fails_the_guard(): void
    {
        $message = $this->failureFrom(fn () => $this->assertQueryReadsExistingColumns(
            DB::table('salez')->where('total', '>', 0)
        ));

        $this->assertNotNull($message, 'A missing table slipped through the guard.');
        $this->assertStringContainsString('salez', $message);
    }

    #[Test]
    public function a_declared_column_that_is_not_there_fails_the_guard(): void
    {
        $message = $this->failureFrom(fn () => $this->assertColumnsExist('sales', ['totl']));

        $this->assertNotNull($message, 'A missing declared column slipped through the guard.');
        $this->assertStringContainsString('totl', $message);
    }

    #[Test]
    public function a_correct_query_and_a_correct_declaration_pass(): void
    {
        $this->assertNull($this->failureFrom(fn () => $this->assertQueryReadsExistingColumns(
            DB::table('sales')
                ->join('sales_items', 'sales_items.sales_id', '=', 'sales.id')
                ->select('sales.id', 'sales.total')
                ->where('sales.status', '!=', 'x')
        )));

        $this->assertNull($this->failureFrom(fn () => $this->assertColumnsExist('sales', ['tax_id', 'total'])));
    }

    /**
     * The exact query that used to be vacuous: products has no stock
     * column, and SQLite answers a quoted unknown column with a literal
     * string rather than an error.
     */
    #[Test]
    public function the_query_that_used_to_pass_silently_now_fails(): void
    {
        $message = $this->failureFrom(fn () => $this->assertQueryReadsExistingColumns(
            DB::table('products')->select('id', 'stock_quantity')->where('stock_quantity', '<', 0)
        ));

        $this->assertNotNull($message, 'The stock_quantity query is passing again.');
        $this->assertStringContainsString('stock_quantity', $message);
    }
}
