<div class="modal modal-blur fade" id="keyboardShortcutsModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('messages.keyboard_shortcuts_title') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>{{ __('messages.keyboard_shortcuts_intro') }}</p>
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>{{ __('messages.keyboard_shortcuts_shortcut_header') }}</th>
                            <th>{{ __('messages.keyboard_shortcuts_action_header') }}</th>
                        </tr>
                    </thead>
                    <tbody id="keyboardShortcutsList"
    data-show-help-modal="{{ __('messages.shortcut_show_help_modal') }}"
    data-focus-search="{{ __('messages.shortcut_focus_search') }}"
    data-close-modal="{{ __('messages.shortcut_close_modal') }}"
    data-create-new-sales-order="{{ __('messages.shortcut_create_new_sales_order') }}"
    data-create-new-purchase-order="{{ __('messages.shortcut_create_new_purchase_order') }}"
    data-save-purchase-order="{{ __('messages.shortcut_save_purchase_order') }}"
    data-save-sales-order="{{ __('messages.shortcut_save_sales_order') }}"
>
                        <!-- Shortcuts will be loaded here dynamically -->
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn me-auto" data-bs-dismiss="modal">{{ __('messages.close') }}</button>
            </div>
        </div>
    </div>
</div>