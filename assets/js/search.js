document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('dashboard-search');
    if (!searchInput) return;

    searchInput.addEventListener('input', function () {
        const query = this.value.toLowerCase();

        // Target 1: Traditional Tables
        const table = document.querySelector('.table');
        if (table) {
            const rows = table.querySelectorAll('tbody tr');
            filterElements(rows, query, table.querySelector('tbody'));
        }

        // Target 2: Cards (for pages like Received Cases)
        const cardsContainer = document.querySelector('.row');
        if (cardsContainer) {
            const cards = cardsContainer.querySelectorAll('.col-12');
            filterElements(cards, query, cardsContainer);
        }
    });

    function filterElements(elements, query, container) {
        let visibleCount = 0;
        elements.forEach(el => {
            // Skip "No results" messages from previous runs
            if (el.classList.contains('no-results-message')) return;

            const text = el.textContent.toLowerCase();
            if (text.includes(query)) {
                el.style.display = '';
                visibleCount++;
            } else {
                el.style.display = 'none';
            }
        });

        // Handle "No results" feedback
        let noResultsMsg = container.querySelector('.no-results-message');
        if (visibleCount === 0 && query !== '') {
            if (!noResultsMsg) {
                if (container.tagName === 'TBODY') {
                    noResultsMsg = document.createElement('tr');
                    noResultsMsg.innerHTML = `<td colspan="100%" class="text-center py-4 text-muted">No matches found for "${query}"</td>`;
                } else {
                    noResultsMsg = document.createElement('div');
                    noResultsMsg.className = 'col-12 text-center py-5 text-muted';
                    noResultsMsg.innerHTML = `<i class="fas fa-search fa-3x mb-3 opacity-25"></i><h5>No matches found</h5><p>No results for "${query}"</p>`;
                }
                noResultsMsg.classList.add('no-results-message');
                container.appendChild(noResultsMsg);
            }
        } else if (noResultsMsg) {
            noResultsMsg.remove();
        }
    }
});
