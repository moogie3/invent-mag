import { handleEditTaxModal } from './partials/tax/handleEditTaxModal.js';
import { handleTaxActions } from './partials/tax/handleTaxActions.js';
import { onPageLoad } from './utils/onPageLoad.js';

// No keyboard shortcuts here: tax rates change rarely, the create/save modals
// are one click away, and unguarded alt+n/ctrl+s registrations hijacked those
// keys on every other page (load-order winners).
onPageLoad(function () {
    handleEditTaxModal();
    handleTaxActions();
});
