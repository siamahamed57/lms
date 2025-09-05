document.addEventListener("DOMContentLoaded", function () {
    const contentDiv = document.querySelector("#dashboard-content");

    function loadPage(pageName) {
        fetch(`./api/courses/${pageName}.php`)
            .then(res => res.text())
            .then(data => {
                contentDiv.innerHTML = data;
            })
            .catch(() => {
                contentDiv.innerHTML = `<p>⚠️ Page not found!</p>`;
            });
    }

    // Default page load
    const urlParams = new URLSearchParams(window.location.search);
    const page = urlParams.get("page");
    if (page) loadPage(page);

    // Sidebar click events
    document.querySelectorAll(".sidebar a").forEach(link => {
        link.addEventListener("click", function (e) {
            e.preventDefault();
            const pageName = this.getAttribute("data-page");
            window.history.pushState({}, "", `?page=${pageName}`);
            loadPage(pageName);
        });
    });

    // Handle back/forward button
    window.addEventListener("popstate", function () {
        const newParams = new URLSearchParams(window.location.search);
        const newPage = newParams.get("page");
        if (newPage) loadPage(newPage);
    });
});
