import * as Turbo from "@hotwired/turbo";
import { onPageLoad } from "./utils/onPageLoad.js";
import { playSuccessSound, playErrorSound } from "./utils/sound.js";
import { formatCurrency } from "../utils/currencyFormatter.js";

// How many autosaves are still in flight, and whether the last one failed.
// A count that only lives in the input field dies with the page, so the
// unload guard below warns while any of those two conditions hold. Kept at
// module scope because the window survives Turbo navigations; each page
// init resets both, so a stale flag from an abandoned page cannot follow
// the user around.
let pendingCountSaves = 0;
let countSaveFailed = false;

window.addEventListener("beforeunload", function(event) {
    if (pendingCountSaves > 0 || countSaveFailed) {
        event.preventDefault();
        event.returnValue = "";
    }
});

onPageLoad(function initStockOpnameModals() {
    const cancelOpnameModal = document.getElementById("cancelOpnameModal");
    const completeOpnameModal = document.getElementById("completeOpnameModal");

    if (cancelOpnameModal && !cancelOpnameModal.dataset.initialized) {
        cancelOpnameModal.dataset.initialized = 'true';

        cancelOpnameModal.addEventListener("show.bs.modal", function(event) {
            const button = event.relatedTarget;
            if (!button) return;

            const actionUrl = button.dataset.actionUrl;
            const cancelForm = document.getElementById("cancelOpnameForm");
            if (cancelForm && actionUrl) {
                cancelForm.action = actionUrl;
            }
        });
    }

    if (completeOpnameModal && !completeOpnameModal.dataset.initialized) {
        completeOpnameModal.dataset.initialized = 'true';

        completeOpnameModal.addEventListener("show.bs.modal", function(event) {
            const button = event.relatedTarget;
            if (!button) return;

            const actionUrl = button.dataset.actionUrl;
            const completeForm = document.getElementById("completeOpnameForm");
            if (completeForm && actionUrl) {
                completeForm.action = actionUrl;
            }
        });
    }
});

/**
 * Save each count the moment its field loses focus.
 *
 * The counting sheet paginates and a page change is a full reload, so a
 * typed-but-unsaved count dies with the old page. Posting one row per
 * blur keeps the stored state current without the user thinking about
 * it, and the Save button stays as the fallback (its POST still
 * redirects and repaints everything from the server). The response
 * carries the recomputed variance, the progress and the whole-opname
 * totals, so the page repaints in place and never goes stale.
 */
onPageLoad(function initCountAutosave() {
    const form = document.getElementById("countsForm");

    if (!form || form.dataset.autosaveInitialized === "true") {
        return;
    }
    form.dataset.autosaveInitialized = "true";

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const stateEl = document.querySelector("[data-count-state]");
    let hideTimer = null;
    let queue = Promise.resolve();

    // A fresh visit starts clean: any warning was about counts on the
    // page being left behind, which the unload guard already handled.
    pendingCountSaves = 0;
    countSaveFailed = false;

    form.addEventListener("change", function(event) {
        const input = event.target;
        if (!(input instanceof HTMLInputElement) || !input.name.startsWith("counts[")) {
            return;
        }

        const value = input.value.trim();
        // An empty box means "nothing counted here yet": the server keeps
        // whatever it stored, so there is nothing to send and nothing to
        // repaint over what is already on screen.
        if (value === "") {
            return;
        }

        const match = input.name.match(/^counts\[(\d+)\]$/);
        if (!match) {
            return;
        }

        // One save at a time, in the order the user made them: two blurs
        // in quick succession must not race, or the older count could
        // land after the newer one.
        queue = queue.then(function() {
            return save(match[1], value);
        });
    });

    function save(productId, value) {
        setState(form.dataset.saving, "text-muted");
        pendingCountSaves++;

        return fetch(form.action, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "Accept": "application/json",
                "X-Requested-With": "XMLHttpRequest",
                "X-CSRF-TOKEN": csrf,
            },
            body: JSON.stringify({ counts: { [productId]: value } }),
        })
            .then(function(response) {
                if (!response.ok) {
                    throw new Error("count save failed");
                }
                return response.json();
            })
            .then(function(data) {
                pendingCountSaves--;
                countSaveFailed = false;
                repaint(productId, data);
                flashRow(productId);
                flashSaved(form.dataset.saved);
                playSuccessSound();
            })
            .catch(function() {
                // The count stays in the field and the Save button still
                // works; the row is simply not persisted yet, and saying
                // so (with the unload guard armed) beats failing silently.
                pendingCountSaves--;
                countSaveFailed = true;
                setState(form.dataset.failed, "text-danger");
                playErrorSound();
            });
    }

    /**
     * Light up the row the count just landed on, so the eye does not
     * have to hunt for which number changed. Restarting the animation
     * needs the reflow: without it, saving the same row twice in a row
     * keeps the first run's state and shows nothing.
     */
    function flashRow(productId) {
        const row = document.querySelector('[data-product-row="' + productId + '"]');
        if (!row) {
            return;
        }

        row.classList.remove("count-saved-flash");
        void row.offsetWidth;
        row.classList.add("count-saved-flash");
    }

    function setState(text, tone) {
        if (!stateEl) {
            return;
        }

        clearTimeout(hideTimer);
        stateEl.textContent = text;
        stateEl.className = "small me-3 " + tone;
        stateEl.hidden = false;
    }

    function flashSaved(text) {
        setState(text, "text-success");
        hideTimer = setTimeout(function() {
            if (stateEl) {
                stateEl.hidden = true;
            }
        }, 2000);
    }

    function repaint(productId, data) {
        const row = document.querySelector('[data-product-row="' + productId + '"]');
        const rowData = data.rows ? data.rows[productId] : null;

        if (row && rowData) {
            const variance = rowData.variance;

            row.classList.remove("table-success", "table-danger");
            if (variance > 0) {
                row.classList.add("table-success");
            } else if (variance < 0) {
                row.classList.add("table-danger");
            }

            const varianceText = row.querySelector("[data-variance-text]");
            if (varianceText) {
                if (variance === null) {
                    varianceText.textContent = "-";
                    varianceText.className = "text-muted";
                } else if (variance > 0) {
                    varianceText.textContent = "+" + variance;
                    varianceText.className = "text-success";
                } else if (variance < 0) {
                    varianceText.textContent = String(variance);
                    varianceText.className = "text-danger";
                } else {
                    varianceText.textContent = "0";
                    varianceText.className = "text-muted";
                }
            }

            const valueCell = row.querySelector("[data-variance-value-cell]");
            if (valueCell) {
                if (rowData.variance_value_formatted) {
                    valueCell.textContent = rowData.variance_value_formatted;
                } else {
                    valueCell.innerHTML = '<span class="text-muted">-</span>';
                }
            }
        }

        const progressText = document.querySelector("[data-counting-progress-text]");
        const progressBar = document.querySelector("[data-counting-progress-bar]");

        if (data.progress) {
            if (progressText && progressText.dataset.template) {
                progressText.textContent = progressText.dataset.template
                    .replace(":counted", data.progress.counted)
                    .replace(":total", data.progress.total);
            }

            if (progressBar) {
                const total = data.progress.total;
                const percent = total > 0 ? Math.round((data.progress.counted * 100) / total) : 0;
                progressBar.style.width = percent + "%";
                progressBar.setAttribute("aria-valuenow", String(data.progress.counted));
                progressBar.setAttribute("aria-valuemax", String(total));
            }
        }

        if (data.totals) {
            const block = document.querySelector("[data-totals-block]");
            if (block) {
                block.hidden = false;
            }

            setTotal("[data-total-counted]", data.totals.counted_total);
            setTotal(
                "[data-total-variance]",
                (data.totals.variance_total > 0 ? "+" : "") + data.totals.variance_total
            );
            setTotal("[data-total-excess]", data.totals.excess_total_formatted);
            setTotal("[data-total-shrinkage]", data.totals.shrinkage_total_formatted);
        }
    }

    function setTotal(selector, text) {
        const element = document.querySelector(selector);
        if (element) {
            element.textContent = text;
        }
    }
});

/**
 * Print the counting sheet.
 *
 * The print CSS in stock-opname.css does the layout work (masthead,
 * ruled boxes for uncounted rows, repeated column heads); this only has
 * to ask the browser for it, since the button cannot carry an inline
 * handler under the CSP.
 */
onPageLoad(function initPrintSheet() {
    const button = document.querySelector("[data-print-sheet]");

    if (!button || button.dataset.initialized === "true") {
        return;
    }
    button.dataset.initialized = "true";

    button.addEventListener("click", function() {
        window.print();
    });
});

/**
 * Keyboard flow down the sheet.
 *
 * The search box doubles as the scan target: a scanner types the
 * barcode and sends Enter, so Enter jumps to that product's row when it
 * is on this page and falls through to the server-side search when it
 * is not (a sheet paginates at 50, so "not on this page" is the common
 * case). Enter inside a count field moves to the next row's field, the
 * way a counter works down a clipboard; the last field keeps the native
 * submit, so Enter still saves everything as the fallback.
 */
onPageLoad(function initSheetKeyboard() {
    const searchInput = document.querySelector("[data-scan-input]");

    if (searchInput && searchInput.dataset.scanInitialized !== "true") {
        searchInput.dataset.scanInitialized = "true";

        searchInput.addEventListener("keydown", function(event) {
            if (event.key !== "Enter") {
                return;
            }

            const code = searchInput.value.trim();
            if (code === "") {
                return;
            }

            const row = findRowByBarcode(code);
            if (row) {
                event.preventDefault();
                focusCountInput(row);
            }
            // No match here: let the form GET-submit and let the server
            // search the whole sheet, barcode included.
        });
    }

    const form = document.getElementById("countsForm");

    if (form && form.dataset.keyboardInitialized !== "true") {
        form.dataset.keyboardInitialized = "true";

        form.addEventListener("keydown", function(event) {
            const input = event.target;
            if (!(input instanceof HTMLInputElement) || event.key !== "Enter") {
                return;
            }

            const inputs = Array.from(form.querySelectorAll('input[name^="counts["]'));
            const index = inputs.indexOf(input);
            if (index === -1) {
                return;
            }

            const next = inputs[index + 1];
            if (next) {
                event.preventDefault();
                next.focus();
                next.select();
            }
            // Last field: no preventDefault, the form submits exactly as
            // it always did.
        });
    }
});

function findRowByBarcode(code) {
    return (
        Array.from(document.querySelectorAll("[data-product-row]")).find(
            function(row) {
                return row.dataset.barcode !== undefined && row.dataset.barcode === code;
            }
        ) || null
    );
}

function focusCountInput(row) {
    row.scrollIntoView({ block: "center", behavior: "smooth" });

    const input = row.querySelector('input[name^="counts["]');
    if (input) {
        input.focus();
        input.select();
    }
}

/**
 * Show the journal completion will book before it books it.
 *
 * The modal used to ask for confirmation while the amounts stayed
 * behind the confirm click. The figures come from the read-only preview
 * endpoint (the same arithmetic as complete), so what renders here is
 * what the confirm posts. A failed fetch simply leaves the plain
 * confirmation: a preview is advisory, never a gate.
 */
onPageLoad(function initCompletePreview() {
    const modal = document.getElementById("completeOpnameModal");

    if (!modal || modal.dataset.previewInitialized === "true") {
        return;
    }
    modal.dataset.previewInitialized = "true";

    const block = modal.querySelector("[data-preview-block]");
    const linesEl = modal.querySelector("[data-preview-lines]");

    if (!block || !linesEl) {
        return;
    }

    modal.addEventListener("show.bs.modal", function(event) {
        const button = event.relatedTarget;
        const url = button && button.dataset.previewUrl;
        if (!url) {
            return;
        }

        // Hide the previous figure while the fresh one loads: a stale
        // number from the last open is worse than none.
        block.classList.add("d-none");
        linesEl.innerHTML = "";

        fetch(url, { headers: { Accept: "application/json" } })
            .then(function(response) {
                return response.ok ? response.json() : Promise.reject(new Error("preview failed"));
            })
            .then(function(data) {
                renderCompletionPreview(data, block, linesEl);
            })
            .catch(function() {
                // Advisory: the confirmation stays usable without it.
            });
    });
});

function renderCompletionPreview(data, block, linesEl) {
    const excess = Number((data.totals && data.totals.excess) || 0);
    const shrinkage = Number((data.totals && data.totals.shrinkage) || 0);
    let html = "";

    if (excess <= 0 && shrinkage <= 0) {
        html = '<div class="text-muted small">' + escapePreviewHtml(block.dataset.msgNoAdjustments || "") + "</div>";
    } else if (!data.will_post) {
        // Stock still moves; only the journal stays silent.
        html = totalsHtml(excess, shrinkage, block);
        html += '<div class="text-muted small">' + escapePreviewHtml(block.dataset.msgNoJournal || "") + "</div>";
    } else {
        let rows = "";
        const lines = data.lines || [];

        for (let i = 0; i < lines.length; i++) {
            const line = lines[i];
            rows +=
                "<tr><td>" +
                escapePreviewHtml(String(line.account_name)) +
                '</td><td class="text-end text-muted">' +
                escapePreviewHtml(line.type === "debit" ? block.dataset.labelDebit || "" : block.dataset.labelCredit || "") +
                '</td><td class="text-end fw-bold">' +
                formatCurrency(Number(line.amount)) +
                "</td></tr>";
        }

        html = '<table class="table table-sm mb-1"><tbody>' + rows + "</tbody></table>";
        html += totalsHtml(excess, shrinkage, block);
    }

    linesEl.innerHTML = html;
    block.classList.remove("d-none");
}

function totalsHtml(excess, shrinkage, block) {
    const parts = [];

    if (excess > 0) {
        parts.push((block.dataset.labelExcess || "") + " " + formatCurrency(excess));
    }
    if (shrinkage > 0) {
        parts.push((block.dataset.labelShrinkage || "") + " " + formatCurrency(shrinkage));
    }

    if (parts.length === 0) {
        return "";
    }

    return '<div class="text-muted small">' + escapePreviewHtml(parts.join(" | ")) + "</div>";
}

function escapePreviewHtml(value) {
    return value
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;");
}