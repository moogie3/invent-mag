export function getStatusBadgeHtml(status, dueDate) {
    let badgeClass = "";
    let statusText = status;

    const today = new Date();
    const due = new Date(dueDate);
    today.setHours(0, 0, 0, 0);
    due.setHours(0, 0, 0, 0);

    if (status === "Paid") {
        badgeClass = "bg-success-lt";
    } else if (status === "Unpaid" && due < today) {
        badgeClass = "bg-danger-lt";
        statusText = "Overdue";
    } else if (status === "Unpaid") {
        badgeClass = "bg-warning-lt";
    } else {
        badgeClass = "bg-secondary-lt";
    }
    return `<span class="badge ${badgeClass}">${statusText}</span>`;
}
