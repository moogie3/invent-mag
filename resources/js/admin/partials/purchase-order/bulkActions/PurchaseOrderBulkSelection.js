export class PurchaseOrderBulkSelection {
    constructor() {
        this.selectAllCheckbox = null;
        this.rowCheckboxes = null;
        this.bulkActionsBar = null;
        this.selectedCount = null;
        this.isInitialized = false;

        this.init();
    }

    init() {
        if (this.isInitialized) {
            return;
        }

        const maxAttempts = 5;
        let attempts = 0;

        const tryInit = () => {
            attempts++;

            this.selectAllCheckbox = document.getElementById("selectAll");
            this.rowCheckboxes = document.querySelectorAll(".row-checkbox");
            this.bulkActionsBar = document.getElementById("bulkActionsBar");
            this.selectedCount = document.getElementById("selectedCount");

            if (
                !this.selectAllCheckbox ||
                !this.bulkActionsBar ||
                !this.selectedCount
            ) {
                // console.warn("Bulk selection essential elements not found.");
                if (this.bulkActionsBar) {
                    this.bulkActionsBar.style.display = "none";
                }
                return;
            }

            if (this.rowCheckboxes.length === 0) {
                this.bulkActionsBar.style.display = "none";
                return;
            }

            this.setupEventListeners();
            this.updateUI();
            this.isInitialized = true;
        };

        tryInit();
    }

    setupEventListeners() {
        this.selectAllCheckbox.addEventListener("change", (e) => {
            const isChecked = e.target.checked;
            this.rowCheckboxes.forEach((checkbox) => {
                checkbox.checked = isChecked;
            });
            this.updateUI();
        });

        this.rowCheckboxes.forEach((checkbox) => {
            checkbox.addEventListener("change", () => {
                this.updateSelectAllState();
                this.updateBulkActionsBar();
            });
        });
    }

    updateSelectAllState() {
        const totalCheckboxes = this.rowCheckboxes.length;
        const checkedCheckboxes = document.querySelectorAll(
            ".row-checkbox:checked"
        ).length;

        if (this.selectAllCheckbox) {
            if (checkedCheckboxes === 0) {
                this.selectAllCheckbox.indeterminate = false;
                this.selectAllCheckbox.checked = false;
            } else if (checkedCheckboxes === totalCheckboxes) {
                this.selectAllCheckbox.indeterminate = false;
                this.selectAllCheckbox.checked = true;
            } else {
                this.selectAllCheckbox.indeterminate = true;
                this.selectAllCheckbox.checked = false;
            }
        }
    }

    updateBulkActionsBar() {
        const checkedCount = document.querySelectorAll(
            ".row-checkbox:checked"
        ).length;

        if (this.bulkActionsBar) {
            if (checkedCount > 0) {
                this.bulkActionsBar.style.display = "block";
                this.selectedCount.textContent = checkedCount;
            } else {
                this.bulkActionsBar.style.display = "none";
            }
        }
    }

    updateUI() {
        this.updateBulkActionsBar();
        this.updateSelectAllState();
    }

    clearSelection() {
        this.rowCheckboxes.forEach((checkbox) => {
            checkbox.checked = false;
        });
        this.updateUI();
    }

    getSelectedIds() {
        return Array.from(
            document.querySelectorAll(".row-checkbox:checked")
        ).map((cb) => cb.value);
    }
}
