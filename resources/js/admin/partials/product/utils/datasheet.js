const DATASHEET_CATEGORIES = ["IC", "TRS", "TR", "MOS", "IGBT", "DIO"];
const PART_PATTERN = /\b(?:[a-z]{1,5}\d{2,6}[a-z]{0,3}|\d[a-z]\d{2,6}[a-z]{0,3})\b/i;

export function datasheetUrl(name, categoryName) {
    const raw = String(categoryName || "").trim();
    const candidates = [
        raw.toUpperCase(),
        ...Array.from(raw.matchAll(/\(([^)]*)\)/g), (m) => m[1].trim().toUpperCase()),
    ];
    if (!candidates.some((c) => DATASHEET_CATEGORIES.includes(c))) return null;

    const match = PART_PATTERN.exec(String(name || ""));
    if (!match) return null;

    return (
        "https://www.alldatasheet.com/view.jsp?Searchword=" +
        encodeURIComponent(match[0])
    );
}
