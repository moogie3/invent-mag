export function formatDateToCustomString(dateString) {
    if (!dateString) return "N/A";
    const options = {
        day: "numeric",
        month: "long",
        year: "numeric",
    };
    return new Date(dateString).toLocaleDateString(
        "en-US",
        options
    );
}
