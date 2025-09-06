export function setDeleteFormAction(action) {
    const deleteForm = document.getElementById("deleteForm");
    if (deleteForm) {
        deleteForm.action = action;
    }
}

window.setDeleteFormAction = setDeleteFormAction;
