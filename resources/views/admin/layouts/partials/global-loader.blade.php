<div id="global-loader" class="page-loader" style="display: flex; opacity: 1; pointer-events: all; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; z-index: 10000; justify-content: center; align-items: center; background-color: rgba(255, 255, 255, 0.9); backdrop-filter: blur(3px);">
    <style nonce="{{ $cspNonce ?? '' }}">
        [data-bs-theme="dark"] #global-loader {
            background-color: rgba(24, 36, 51, 0.9) !important;
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
