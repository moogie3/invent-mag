import { onPageLoad } from '../utils/onPageLoad.js';

onPageLoad(function() {
    var upgradeModal = document.getElementById('upgradeModal');
    if (!upgradeModal) return;
    
    var configEl = document.getElementById('upgrade-config');
    var upgradeTo = configEl ? configEl.getAttribute('data-upgrade-to') : 'Upgrade to';
    var confirmUpgrade = configEl ? configEl.getAttribute('data-confirm-upgrade') : 'Confirm Upgrade';

    if (!upgradeModal.dataset.listenerAttached) {
        upgradeModal.dataset.listenerAttached = 'true';
        upgradeModal.addEventListener('show.bs.modal', function(event) {
            var button = event.relatedTarget;
            if (!button) return;
            var planName = button.getAttribute('data-plan-name');
            var planPrice = button.getAttribute('data-plan-price');
            var planSlug = button.getAttribute('data-plan-slug');
            var isRenewal = button.getAttribute('data-is-renewal') === 'true';

            var modalPlanName = document.getElementById('modalPlanName');
            if(modalPlanName) modalPlanName.textContent = planName;
            
            var modalPlanPrice = document.getElementById('modalPlanPrice');
            if(modalPlanPrice) modalPlanPrice.textContent = planPrice;
            
            var modalPlanSlug = document.getElementById('modalPlanSlug');
            if(modalPlanSlug) modalPlanSlug.value = planSlug;
            
            var actionText = document.getElementById('modalActionText');
            var renewalInfoBox = document.getElementById('renewalInfoBox');
            
            if (isRenewal) {
                if(actionText) actionText.textContent = "Renew";
                var modalDaysRemaining = document.getElementById('modalDaysRemaining');
                if(modalDaysRemaining) modalDaysRemaining.textContent = button.getAttribute('data-days-remaining');
                
                var modalNewDays = document.getElementById('modalNewDays');
                if(modalNewDays) modalNewDays.textContent = button.getAttribute('data-new-days');
                
                var modalNewDate = document.getElementById('modalNewDate');
                if(modalNewDate) modalNewDate.textContent = button.getAttribute('data-new-date');
                
                if(renewalInfoBox) renewalInfoBox.classList.remove('d-none');
            } else {
                if(actionText) {
                    actionText.textContent = upgradeTo;
                }
                if(renewalInfoBox) renewalInfoBox.classList.add('d-none');
            }
        });
    }

    // Handle form submission for Turbo
    var upgradeForm = document.getElementById('upgradeForm');
    if (upgradeForm && !upgradeForm.dataset.listenerAttached) {
        upgradeForm.dataset.listenerAttached = 'true';
        upgradeForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            var formData = new FormData(upgradeForm);
            var submitBtn = upgradeForm.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" style="width: 1rem; height: 1rem;" role="status" aria-hidden="true"></span>Processing...';
            }

            // Get CSRF token
            var csrfMeta = document.querySelector('meta[name="csrf-token"]');
            var csrfInput = document.querySelector('input[name="_token"]');
            var csrfToken = (csrfMeta && csrfMeta.content) || (csrfInput && csrfInput.value) || '';

            fetch(upgradeForm.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                if (data.redirect) {
                    window.location.href = data.redirect;
                } else if (data.error) {
                    if (window.InventMagApp && window.InventMagApp.showToast) {
                        window.InventMagApp.showToast('Error', data.error, 'error');
                    } else {
                        alert(data.error);
                    }
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="ti ti-check me-2"></i>' + confirmUpgrade;
                    }
                }
            })
            .catch(function(error) {
                console.error('Error:', error);
                if (window.InventMagApp && window.InventMagApp.showToast) {
                    window.InventMagApp.showToast('Error', 'An error occurred. Please try again.', 'error');
                } else {
                    alert('An error occurred. Please try again.');
                }
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="ti ti-check me-2"></i>' + confirmUpgrade;
                }
            });
        });
    }
});
