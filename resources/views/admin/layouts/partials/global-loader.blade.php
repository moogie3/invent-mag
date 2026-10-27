<div id="global-loader" class="page-loader" style="display: flex; opacity: 1; pointer-events: all; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; z-index: 10000; justify-content: center; align-items: center; background-color: rgba(255, 255, 255, 0.9); backdrop-filter: blur(3px);">
    <style nonce="{{ $cspNonce ?? '' }}">
        [data-bs-theme="dark"] #global-loader {
            /* frosted cool-gray body #1c1e20 - rgba(24,36,51) was the retired
               dark-blue palette; the inline style on the div above is the light
               fallback, and this !important rule is what dark mode paints */
            background-color: rgba(28, 30, 32, 0.92) !important;
        }
    </style>
    <div class="container-slim py-4">
        <div class="text-center">
            <div class="mb-3">
                <a class="navbar-brand navbar-brand-autodark">
                    <i class="ti ti-brand-minecraft fs-2 me-2"></i>Invent-MAG
                </a>
            </div>
            <div class="spinner-border text-primary" role="status"></div>
        </div>
    </div>
</div>
