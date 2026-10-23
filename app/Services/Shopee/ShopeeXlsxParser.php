<?php

namespace App\Services\Shopee;

use InvalidArgumentException;
use OpenSpout\Reader\XLSX\Reader;

/**
 * Parses Shopee Seller Centre "mass update" XLSX exports.
 *
 * File layout (all four templates: basic_info, media_info, sales_info, shipping_info):
 *   Row 1: internal column keys (e.g. et_title_product_id, ps_item_cover_image)
 *   Row 2: metadata row — [template type, hash, 0, store id, search conditions JSON]
 *   Row 3+: display headers / requirement hints (locale-dependent, ignored)
 *   Data rows: first cell is the numeric Shopee product_id
 *
 * We key columns by the row-1 internal keys (locale-independent) and detect data
 * rows by the numeric product_id in column A, which is robust across locales.
 */
class ShopeeXlsxParser
{
    /**
     * @return array<int, array<string, string|int|float|null>>
     *
     * @throws InvalidArgumentException when the file is not a valid Shopee mass-update file
     */
    public function parse(string $path, ?string $expectedType = null): array
    {
        if (! is_file($path)) {
            throw new InvalidArgumentException('File not found.');
        }

        $reader = new Reader;
        $reader->open($path);

        try {
            $sheets = $reader->getSheetIterator();
            $sheets->rewind();
            if (! $sheets->valid()) {
                throw new InvalidArgumentException('The spreadsheet is empty.');
            }

            $rows = [];
            $headers = [];
            $metaChecked = $expectedType === null;
            $index = 0;

            foreach ($sheets->current()->getRowIterator() as $row) {
                $index++;

                $cells = [];
                foreach ($row->getCells() as $columnIndex => $cell) {
                    $cells[$columnIndex] = $cell->getValue();
                }

                if ($index === 1) {
                    foreach ($cells as $col => $value) {
                        $headers[$col] = trim((string) $value);
                    }

                    continue;
                }

                if ($index === 2) {
                    $type = trim((string) ($cells[0] ?? ''));
                    if (! $metaChecked) {
                        $this->assertType($type, $expectedType);
                        $metaChecked = true;
                    }

                    continue;
                }

                // Data rows: first cell is the numeric Shopee product_id
                $first = trim((string) ($cells[0] ?? ''));
                if ($first === '' || ! ctype_digit($first)) {
                    continue;
                }

                $assoc = [];
                foreach ($cells as $col => $value) {
                    $key = $headers[$col] ?? null;
                    if ($key !== null && $key !== '') {
                        $assoc[$key] = $value;
                    }
                }

                $rows[] = $assoc;
            }

            if (! $metaChecked) {
                throw new InvalidArgumentException('The spreadsheet has no data rows.');
            }

            return $rows;
        } finally {
            $reader->close();
        }
    }

    private function assertType(string $type, ?string $expectedType): void
    {
        $valid = ['basic_info', 'media_info', 'sales_info', 'shipping_info'];

        if (! in_array($type, $valid, true)) {
            throw new InvalidArgumentException(
                'This does not look like a Shopee mass-update file. Download the template again from Shopee Seller Centre.'
            );
        }

        if ($expectedType !== null && $type !== $expectedType) {
            throw new InvalidArgumentException(
                "Expected a Shopee \"{$expectedType}\" file but the uploaded file is \"{$type}\". Please upload the correct file."
            );
        }
    }
}
