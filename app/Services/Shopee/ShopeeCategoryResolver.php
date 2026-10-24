<?php

namespace App\Services\Shopee;

use App\Models\Categories;

/**
 * Maps imported products onto the shop's 35-category SKU taxonomy
 * (config/shopee_categories.php, sourced from SKU_Stock_Guide.docx).
 *
 * Resolution order:
 *   1. SKU prefix — the guide codes every product by category (TRS-0001-A),
 *      so a valid prefix/alias is an exact, conflict-free hit.
 *   2. Product-name keywords (English + Indonesian), most specific category
 *      first — config order decides ties ("Transistor IGBT Mosfet" → IGBT).
 *   3. Shopee category path leaf equal to an existing local category name.
 *   4. The guide's catch-all (MISC / Other Spare Part).
 *
 * Categories are firstOrCreate'd per tenant (name = prefix,
 * description = label); an existing category with the same prefix/alias or
 * description label is reused rather than duplicated.
 */
class ShopeeCategoryResolver
{
    /** @var array<int, array{prefix: string, label: string, aliases: array<int, string>, keywords: array<int, string>}> */
    private array $entries;

    private string $fallback;

    public function __construct()
    {
        $this->entries = config('shopee_categories.entries', []);
        $this->fallback = (string) config('shopee_categories.fallback', 'MISC');
    }

    /**
     * @param  string  $code  products.code / Shopee SKU Induk (may be empty or numeric)
     * @param  string  $name  product display name
     * @param  string  $categoryPath  raw Shopee path, e.g. "101798 - Home & Living/Tools/Pliers"
     */
    public function resolve(string $code, string $name, string $categoryPath = ''): Categories
    {
        // Tier 1 — SKU prefix (exact taxonomy from the guide).
        $prefix = strtoupper(trim((string) explode('-', strtoupper(trim($code)))[0]));
        if ($prefix !== '') {
            foreach ($this->entries as $entry) {
                $candidates = array_map('strtoupper', array_merge([$entry['prefix']], $entry['aliases']));
                if (in_array($prefix, $candidates, true)) {
                    return $this->ensureCategory($entry);
                }
            }
        }

        // Tier 2 — name keywords, most specific category first.
        $lowerName = mb_strtolower($name);
        foreach ($this->entries as $entry) {
            foreach ($entry['keywords'] as $keyword) {
                if ($this->nameMatchesKeyword($lowerName, $keyword)) {
                    return $this->ensureCategory($entry);
                }
            }
        }

        // Tier 3 — legacy behavior: Shopee leaf matches a local category name.
        $leaf = $this->shopeeLeaf($categoryPath);
        if ($leaf !== '') {
            $match = Categories::query()
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($leaf)])
                ->first();
            if ($match) {
                return $match;
            }
        }

        // Tier 4 — catch-all (guide's "Other Spare Part").
        return $this->ensureCategory($this->fallbackEntry());
    }

    private function nameMatchesKeyword(string $lowerName, string $keyword): bool
    {
        $kw = mb_strtolower(trim($keyword));
        if ($kw === '') {
            return false;
        }

        // Short tokens ("ic", "led", "aki") must be whole words so they never
        // hit inside longer words; longer tokens match as substrings so
        // "kapasitor" or "stop kontak" catch compound names.
        if (mb_strlen($kw) <= 4) {
            return (bool) preg_match('/\b'.preg_quote($kw, '/').'\b/u', $lowerName);
        }

        return str_contains($lowerName, $kw);
    }

    private function shopeeLeaf(string $categoryPath): string
    {
        $leaf = $categoryPath;
        if (str_contains($leaf, '/')) {
            $parts = explode('/', $leaf);
            $leaf = trim((string) end($parts));
        }
        if (preg_match('/^\d+\s*-\s*(.+)$/', $leaf, $m)) {
            $leaf = trim($m[1]);
        }

        return $leaf;
    }

    /** @param  array{prefix: string, label: string, aliases: array<int, string>, keywords: array<int, string>}  $entry */
    private function ensureCategory(array $entry): Categories
    {
        $names = array_merge([$entry['prefix']], $entry['aliases']);

        $existing = Categories::query()
            ->where(function ($query) use ($names, $entry) {
                foreach ($names as $name) {
                    $query->orWhereRaw('LOWER(name) = ?', [mb_strtolower($name)]);
                }
                $query->orWhereRaw("LOWER(COALESCE(description, '')) = ?", [mb_strtolower($entry['label'])]);
            })
            ->first();

        if ($existing) {
            return $existing;
        }

        return Categories::create([
            'name' => $entry['prefix'],
            'description' => $entry['label'],
        ]);
    }

    /** @return array{prefix: string, label: string, aliases: array<int, string>, keywords: array<int, string>} */
    private function fallbackEntry(): array
    {
        foreach ($this->entries as $entry) {
            if ($entry['prefix'] === $this->fallback) {
                return $entry;
            }
        }

        return ['prefix' => $this->fallback, 'label' => 'Other Spare Part', 'aliases' => [], 'keywords' => []];
    }
}
