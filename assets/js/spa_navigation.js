/**
 * OCMS Single Page Navigation (SPA) Module
 * Handles AJAX-based page switching for instant navigation
 */
document.addEventListener('DOMContentLoaded', () => {
    const mainArea = document.querySelector('.main_content');
    if (!mainArea) return;

    const pageCache = {};
    const loaderId = 'ocms-page-loader';

    // 1. Create Lightweight Loader CSS
    const style = document.createElement('style');
    style.innerHTML = `
        #${loaderId} {
            position: fixed;
            top: 0; left: 0; right: 0; height: 3px;
            background: linear-gradient(90deg, #4f8cff, #3b6fd8);
            z-index: 10002;
            width: 0;
            transition: width 0.3s ease;
            box-shadow: 0 0 10px rgba(59, 140, 255, 0.5);
        }
    `;
    document.head.appendChild(style);

    // Create the Loader Element
    const loader = document.createElement('div');
    loader.id = loaderId;
    document.body.appendChild(loader);

    /**
     * Re-initializes page-specific scripts (Charts, etc.)
     */
    function reinitPageScripts() {
        // Trigger DOMContentLoaded events on the new content
        const event = new Event('DOMContentLoaded', { bubbles: true, cancelable: true });
        window.dispatchEvent(event);
        
        // Re-init sidebar toggle logic
        if (typeof reinitSidebarToggle === 'function') reinitSidebarToggle();
        
        // Custom re-init for known modules
        if (typeof initChart === 'function') initChart(); // For dashboards
        
        // Re-attach search handlers if any
        const searchInput = document.getElementById('dashboard-search');
        if (searchInput && typeof setupSearch === 'function') setupSearch(searchInput);
    }

    /**
     * Core Navigation Logic
     */
    async function navigateTo(url, saveHistory = true) {
        // Don't reload current page
        if (url === window.location.href) return;

        // Show Loader
        loader.style.width = '30%';
        loader.style.opacity = '1';

        try {
            let content = '';
            if (pageCache[url]) {
                content = pageCache[url];
                loader.style.width = '100%';
            } else {
                const response = await fetch(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const html = await response.text();
                
                // Parse and extract .main_content
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newContent = doc.querySelector('.main_content');
                
                if (newContent) {
                    content = newContent.innerHTML;
                    pageCache[url] = content;
                    loader.style.width = '80%';
                } else {
                    // Fallback to full reload if we can't find core container
                    window.location.href = url;
                    return;
                }
            }

            // Update DOM
            mainArea.innerHTML = content;
            
            // Sync active sidebar state
            updateSidebarActiveLink(url);

            // Finish Loader
            loader.style.width = '100%';
            setTimeout(() => {
                loader.style.opacity = '0';
                setTimeout(() => loader.style.width = '0', 300);
            }, 200);

            // Update URL
            if (saveHistory) {
                history.pushState({ url }, '', url);
            }

            // Scroll to top
            mainArea.scrollTop = 0;

            // Re-init scripts
            reinitPageScripts();

        } catch (error) {
            console.error('Navigation Error:', error);
            window.location.href = url; // Fallback
        }
    }

    function updateSidebarActiveLink(targetUrl) {
        const sidebarLinks = document.querySelectorAll('.sidebar-menu a');
        
        // Use the filename for strict matching (e.g., 'dashboard.php')
        const targetFilename = targetUrl.split('/').pop().split('?')[0] || 'index.php';

        sidebarLinks.forEach(link => {
            const linkUrl = new URL(link.href, window.location.origin);
            const linkFilename = linkUrl.pathname.split('/').pop();
            
            // Strict reset
            link.classList.remove('active');
            link.classList.remove('selected'); // legacy cleanup
            
            if (linkFilename === targetFilename) {
                link.classList.add('active');
            }
        });
    }

    // Intercept Link Clicks
    document.addEventListener('click', (e) => {
        const link = e.target.closest('a');
        
        // Conditions for AJAX navigation
        if (link && 
            link.hostname === window.location.hostname && // Internal link
            !link.getAttribute('target') && // Not target="_blank"
            !link.href.includes('logout.php') && // Not logout
            !link.classList.contains('no-ajax') && // Manual exclusion
            !link.href.includes('#') // Not an anchor
        ) {
            e.preventDefault();
            navigateTo(link.href);
        }
    });

    // Initial state sync (Fixes hardcoded/duplicate 'active' classes from server-side)
    updateSidebarActiveLink(window.location.href);

    // Handle Back/Forward buttons
    window.addEventListener('popstate', (e) => {
        if (e.state && e.state.url) {
            navigateTo(e.state.url, false);
        } else {
            window.location.reload();
        }
    });
});
