export function initShopeeImport() {
    const form = document.getElementById("shopeeImportForm");
    if (!form || form.dataset.handlerAttached) return;
    form.dataset.handlerAttached = "true";

    const resultBox = document.getElementById("shopeeImportResult");
    const submitBtn = document.getElementById("shopeeImportSubmitBtn");
    const progressEl = document.getElementById("shopeeImportProgress");
    const progressBar = document.getElementById("shopeeImportProgressBar");
    const progressLabel = document.getElementById("shopeeImportProgressLabel");
    const progressPct = document.getElementById("shopeeImportProgressPct");
    const tokenInput = document.getElementById("shopeeImportProgressToken");
    const originalBtnHtml = submitBtn ? submitBtn.innerHTML : "";

    // Translated strings come from the Blade modal as data-* attributes (EN/ID)
    const msg = (key, fallback) => form.dataset[key] || fallback;

    let pollTimer = null;
    let imageTimer = null;
    let imageCapTimer = null;

    function stopPolling() {
        if (pollTimer !== null) {
            clearInterval(pollTimer);
            pollTimer = null;
        }
    }

    function stopImagePolling() {
        if (imageTimer !== null) {
            clearInterval(imageTimer);
            imageTimer = null;
        }
        if (imageCapTimer !== null) {
            clearTimeout(imageCapTimer);
            imageCapTimer = null;
        }
    }

    const modalEl = document.getElementById("shopeeImportModal");
    if (modalEl) {
        modalEl.addEventListener("hidden.bs.modal", () => {
            stopPolling();
            stopImagePolling();
        });
    }

    function template(str, vars) {
        let out = String(str);
        for (const [key, value] of Object.entries(vars)) {
            out = out.split(`:${key}`).join(String(value));
        }
        return out;
    }

    function setProgress(pct, label) {
        if (!progressEl) return;
        progressEl.style.display = "block";
        const value = Math.max(0, Math.min(100, Math.round(pct)));
        if (progressBar) {
            progressBar.style.width = `${value}%`;
            progressBar.setAttribute("aria-valuenow", String(value));
        }
        if (progressPct) progressPct.textContent = `${value}%`;
        if (progressLabel) progressLabel.textContent = label;
    }

    function hideProgress() {
        if (progressEl) progressEl.style.display = "none";
    }

    function generateToken() {
        if (window.crypto && typeof window.crypto.randomUUID === "function") {
            return window.crypto.randomUUID();
        }
        return Date.now().toString(36) + Math.random().toString(36).slice(2, 12);
    }

    function fetchProgress(token) {
        const url = msg("progressUrl", "");
        if (!url) return Promise.resolve(null);
        return fetch(`${url}?token=${encodeURIComponent(token)}`, {
            headers: {
                Accept: "application/json",
                "X-Requested-With": "XMLHttpRequest",
            },
        })
            .then((response) => (response.ok ? response.json() : null))
            .catch(() => null);
    }

    function renderImportPhase(progress) {
        if (!progress) return;

        if (progress.phase === "parsing" || !progress.total) {
            setProgress(
                10,
                msg("labelProgressPreparing", "Preparing files…"),
            );
        } else if (progress.phase === "products") {
            const pct = Math.min(
                99,
                (progress.processed / progress.total) * 100,
            );
            setProgress(
                pct,
                template(
                    msg("labelProgressProducts", ":done / :total products"),
                    { done: progress.processed, total: progress.total },
                ),
            );
        } else if (progress.phase === "queuing_images") {
            setProgress(
                99,
                msg("labelProgressQueueing", "Queueing cover downloads…"),
            );
        } else if (progress.phase === "done") {
            setProgress(
                99,
                msg("labelProgressComplete", "Import complete"),
            );
        }
    }

    function startPolling(token) {
        stopPolling();
        if (!msg("progressUrl", "")) return;
        pollTimer = setInterval(() => {
            fetchProgress(token).then(renderImportPhase);
        }, 750);
    }

    // Second stage: the import POST queued cover downloads; poll the per-import
    // media counters (done/failed counted by the download jobs) until every
    // queued image settles, or fall back to the queue drain (5-minute cap).
    function startImagePhase(token) {
        stopImagePolling();
        if (!msg("progressUrl", "")) return;

        let baseline = null;
        let lastStats = null;
        const imagesLabel = msg(
            "labelProgressImages",
            "Cover images: :pending remaining",
        );
        const completeLabel = msg("labelProgressComplete", "Import complete");
        const mediaDoneLabel = msg(
            "labelMediaDone",
            "All :count cover images imported",
        );
        const mediaPartialLabel = msg(
            "labelMediaPartial",
            ":done of :total cover images imported (:failed failed)",
        );

        const finish = (stats) => {
            stopImagePolling();
            setProgress(100, completeLabel);
            if (stats) {
                appendMediaResult(
                    template(
                        stats.failed > 0 ? mediaPartialLabel : mediaDoneLabel,
                        {
                            count: stats.done,
                            done: stats.done,
                            total: stats.total,
                            failed: stats.failed,
                        },
                    ),
                    stats.failed > 0,
                );
            }
            // Short pause so the updated banner is readable before the reload.
            setTimeout(() => window.location.reload(), 4000);
        };

        const tick = () => {
            fetchProgress(token).then((progress) => {
                if (!progress) return;

                const media = progress.media;
                if (media && typeof media.total === "number" && media.total > 0) {
                    lastStats = media;
                    const settled = media.done + media.failed;
                    const remaining = Math.max(0, media.total - settled);
                    setProgress(
                        (settled / media.total) * 100,
                        template(imagesLabel, { pending: remaining }),
                    );
                    if (settled >= media.total) {
                        finish(media);
                    }
                    return;
                }

                // Fallback: no counters for this import — drain of the (global) queue
                if (typeof progress.queue_pending !== "number") {
                    return;
                }
                const pending = progress.queue_pending;

                if (baseline === null) {
                    if (pending <= 0) {
                        finish(null);
                        return;
                    }
                    baseline = pending;
                }

                if (pending <= 0) {
                    finish(null);
                    return;
                }

                setProgress(
                    ((baseline - pending) / baseline) * 100,
                    template(imagesLabel, { pending }),
                );
            });
        };

        tick();
        imageTimer = setInterval(tick, 1500);
        imageCapTimer = setTimeout(() => {
            stopImagePolling();
            // Out of time — surface the partial state instead of freezing silently.
            if (lastStats && lastStats.done + lastStats.failed < lastStats.total) {
                appendMediaResult(
                    template(mediaPartialLabel, {
                        done: lastStats.done,
                        total: lastStats.total,
                        failed: lastStats.failed,
                    }),
                    true,
                );
            }
        }, 300000);
    }

    form.addEventListener("submit", function (e) {
        e.preventDefault();
        if (form.dataset.submitting === "true") return;
        form.dataset.submitting = "true";

        stopImagePolling();
        const token = generateToken();
        if (tokenInput) tokenInput.value = token;

        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML =
                '<span class="spinner-border spinner-border-sm me-2"></span> ' +
                msg("msgRunning", "Importing...");
        }
        if (resultBox) {
            resultBox.style.display = "none";
            resultBox.innerHTML = "";
        }

        setProgress(
            0,
            msg("labelProgressPreparing", "Preparing files…"),
        );
        startPolling(token);

        fetch(form.action, {
            method: "POST",
            body: new FormData(form),
            headers: {
                "X-Requested-With": "XMLHttpRequest",
                Accept: "application/json",
                "X-Laravel-JSON": "true",
                "X-CSRF-TOKEN":
                    document
                        .querySelector('meta[name="csrf-token"]')
                        ?.getAttribute("content") || "",
            },
        })
            .then((response) => {
                if (response.status === 419) {
                    InventMagApp.showToast(
                        "Error",
                        msg("msgSessionExpired", "Session expired. Please refresh the page and try again."),
                        "error",
                    );
                    setTimeout(() => window.location.reload(), 2000);
                    return null;
                }
                return response
                    .json()
                    .then((data) => ({ ok: response.ok, data }))
                    .catch(() => ({
                        ok: false,
                        data: {
                            success: false,
                            message: msg("msgGenericError", "An error occurred. Please try again."),
                        },
                    }));
            })
            .then((result) => {
                if (!result) return;

                if (result.ok && result.data.success) {
                    InventMagApp.showToast(
                        "Success",
                        result.data.message || msg("msgDone", "Import finished"),
                        "success",
                    );
                    renderSummary(result.data.summary || {}, form, resultBox);
                    form.reset();

                    const imagesQueued = result.data.summary?.images_queued ?? 0;
                    if (imagesQueued > 0) {
                        startImagePhase(token);
                    } else {
                        setProgress(
                            100,
                            msg("labelProgressComplete", "Import complete"),
                        );
                        setTimeout(() => window.location.reload(), 4000);
                    }
                } else {
                    InventMagApp.showToast(
                        "Error",
                        result.data.message || msg("msgFailed", "Import failed"),
                        "error",
                    );
                    if (result.data.summary) {
                        renderSummary(result.data.summary, form, resultBox);
                    }
                }
            })
            .catch(() => {
                InventMagApp.showToast(
                    "Error",
                    msg("msgGenericError", "An error occurred. Please try again."),
                    "error",
                );
            })
            .finally(() => {
                stopPolling();
                form.dataset.submitting = "false";
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnHtml;
                }
            });
    });
}

function renderSummary(summary, form, resultBox) {
    if (!resultBox) return;

    const label = (key) => form.dataset[key] || key;
    const rows = [
        [label("labelUpdated"), summary.updated ?? 0],
        [label("labelCreated"), summary.created ?? 0],
        [label("labelSkipped"), summary.skipped_plan_limit ?? 0],
        [label("labelImages"), summary.images_queued ?? 0],
        [label("labelSplitFamilies"), summary.split_families ?? 0],
        [label("labelErrors"), (summary.errors || []).length],
    ];

    let html =
        '<div class="alert alert-success py-2"><div class="row text-center">' +
        rows
            .map(
                ([lbl, val]) =>
                    `<div class="col"><div class="fw-bold fs-3">${val}</div><div class="text-muted small">${escapeHtml(lbl)}</div></div>`,
            )
            .join("") +
        "</div>";

    if (summary.errors && summary.errors.length) {
        const shown = summary.errors.slice(0, 10);
        const moreLabel = (form.dataset.labelMore || "…and :count more").replace(
            ":count",
            summary.errors.length - shown.length,
        );
        html +=
            '<hr class="my-2"><ul class="mb-0 small text-danger">' +
            shown.map((err) => `<li>${escapeHtml(err)}</li>`).join("") +
            (summary.errors.length > shown.length
                ? `<li class="text-muted">${escapeHtml(moreLabel)}</li>`
                : "") +
            "</ul>";
    }

    html += "</div>";
    resultBox.innerHTML = html;
    resultBox.style.display = "block";
}

/**
 * Append (or replace) the media-completion line inside the green summary
 * banner, so the result reflects the media phase too — not just the counts
 * from the initial import response.
 */
function appendMediaResult(text, isWarning) {
    const resultBox = document.getElementById("shopeeImportResult");
    if (!resultBox) return;
    const alert = resultBox.querySelector(".alert");
    if (!alert) return;

    const existing = alert.querySelector(".media-result-line");
    if (existing) existing.remove();

    const line = document.createElement("div");
    line.className = `media-result-line small fw-semibold mt-2 ${isWarning ? "text-warning" : "text-success"}`;
    line.innerHTML = `${
        isWarning
            ? '<i class="ti ti-alert-triangle me-1"></i>'
            : '<i class="ti ti-circle-check me-1"></i>'
    }${escapeHtml(text)}`;
    alert.appendChild(line);
}

function escapeHtml(text) {
    const div = document.createElement("div");
    div.textContent = String(text);
    return div.innerHTML;
}

window.initShopeeImport = initShopeeImport;
