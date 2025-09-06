export function showContentAfterLoading() {
    setTimeout(function () {
        const loadingContainer = document.getElementById("loading-container");
        const authContent = document.getElementById("auth-content");

        if (loadingContainer) loadingContainer.style.display = "none";
        if (authContent) authContent.style.display = "block";
    }, 800);
}
