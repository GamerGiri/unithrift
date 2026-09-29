// assets/js/filter.js
// Client-side instant Search & Multi-Filter Engine for UniThrift Marketplace

document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('marketplaceSearch');
    const categoryPills = document.querySelectorAll('.filter-pill');
    const conditionSelect = document.getElementById('conditionFilter');
    const sortSelect = document.getElementById('sortFilter');
    const itemsGrid = document.getElementById('marketplaceGrid');
    const resultsCountEl = document.getElementById('resultsCount');

    if (!itemsGrid) return;

    let activeCategory = 'All';

    // Event Listeners
    if (searchInput) {
        searchInput.addEventListener('input', debounce(filterItems, 150));
    }

    categoryPills.forEach(pill => {
        pill.addEventListener('click', () => {
            categoryPills.forEach(p => p.classList.remove('active'));
            pill.classList.add('active');
            activeCategory = pill.getAttribute('data-category') || 'All';
            filterItems();
        });
    });

    if (conditionSelect) {
        conditionSelect.addEventListener('change', filterItems);
    }

    if (sortSelect) {
        sortSelect.addEventListener('change', filterItems);
    }

    function filterItems() {
        const query = (searchInput ? searchInput.value : '').toLowerCase().trim();
        const selectedCondition = conditionSelect ? conditionSelect.value : 'All';
        const selectedSort = sortSelect ? sortSelect.value : 'newest';

        const cards = Array.from(itemsGrid.querySelectorAll('.item-card'));
        let visibleCount = 0;

        cards.forEach(card => {
            const title = (card.getAttribute('data-title') || '').toLowerCase();
            const desc = (card.getAttribute('data-desc') || '').toLowerCase();
            const course = (card.getAttribute('data-course') || '').toLowerCase();
            const category = card.getAttribute('data-category') || '';
            const condition = card.getAttribute('data-condition') || '';

            // Matching logic
            const matchesQuery = !query || 
                title.includes(query) || 
                desc.includes(query) || 
                course.includes(query);

            const matchesCategory = (activeCategory === 'All') || (category === activeCategory);
            const matchesCondition = (selectedCondition === 'All') || (condition === selectedCondition);

            if (matchesQuery && matchesCategory && matchesCondition) {
                card.style.display = 'flex';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        // Sorting visible cards
        sortCards(cards, selectedSort);

        // Update count indicator
        if (resultsCountEl) {
            resultsCountEl.textContent = `${visibleCount} item${visibleCount === 1 ? '' : 's'} found`;
        }

        // Handle empty state
        const emptyState = document.getElementById('noResultsMessage');
        if (emptyState) {
            emptyState.style.display = visibleCount === 0 ? 'block' : 'none';
        }
    }

    function sortCards(cards, sortOption) {
        const sorted = cards.slice().sort((a, b) => {
            const priceA = parseFloat(a.getAttribute('data-price')) || 0;
            const priceB = parseFloat(b.getAttribute('data-price')) || 0;
            const discountA = parseInt(a.getAttribute('data-discount')) || 0;
            const discountB = parseInt(b.getAttribute('data-discount')) || 0;
            const idA = parseInt(a.getAttribute('data-id')) || 0;
            const idB = parseInt(b.getAttribute('data-id')) || 0;

            switch (sortOption) {
                case 'price_asc':
                    return priceA - priceB;
                case 'price_desc':
                    return priceB - priceA;
                case 'discount_desc':
                    return discountB - discountA;
                case 'newest':
                default:
                    return idB - idA;
            }
        });

        sorted.forEach(card => itemsGrid.appendChild(card));
    }

    function debounce(func, wait) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }
});
