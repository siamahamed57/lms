$(document).ready(function() {
    const contentBody = $(".content-body");

    function loadPage(url, pushState = true) {
        // Add a loading/transition effect
        contentBody.css('opacity', 0.5);

        // Construct the AJAX URL. It should always be dashboard.php
        // with the query parameters from the clicked link, plus ajax=1.
        const ajaxUrl = new URL(url, window.location.href);
        ajaxUrl.searchParams.set('ajax', '1');
        
        // The page key for highlighting the active menu item
        const pageKey = new URLSearchParams(url.split('?')[1] || '').get('page') || 'overview';

        $.ajax({
            url: ajaxUrl.pathname,
            type: "GET",
            data: ajaxUrl.search.substring(1), // Pass the full query string
            success: function(data) {
                contentBody.html(data);
                contentBody.css('opacity', 1);

                if (pushState) {
                    // Update URL in the browser's history
                    window.history.pushState({ path: url }, "", url);
                }

                // Update active link in both side and mobile navs
                $("a[data-section]").removeClass("active");
                $(`a[data-section='${pageKey}']`).addClass("active");
            },
            error: function(jqXHR) {
                contentBody.html(`<div class='p-8 text-center'><h2 class='text-2xl font-bold text-red-600'>❌ Error Loading Page</h2><p class='text-gray-400'>Could not load content. Status: ${jqXHR.status}</p></div>`);
                contentBody.css('opacity', 1);
            }
        });
    }

    // Handle clicks on all internal dashboard links
    $(document).on("click", "a", function(e) {
        const href = $(this).attr('href');

        // Only intercept links pointing to the dashboard
        if (href && (href.startsWith('?page=') || href.startsWith('dashboard?page='))) {
            e.preventDefault();
            const relativeUrl = '?' + (href.split('?')[1] || '');
            loadPage(relativeUrl);
        }
    });

    // Handle browser back/forward buttons for a seamless experience
    window.onpopstate = function(event) {
        const path = (event.state && event.state.path) ? event.state.path : window.location.search;
        if (path) {
            loadPage(path, false); // Don't push state on pop
        }
    };
});
