(function () {
    const root = document.getElementById('team4all-root');
    if (!root) {
        return;
    }

    root.dataset.initialized = 'true';

    const search = document.getElementById('team4all-contact-search');
    if (!search) {
        return;
    }

    const groups = Array.from(root.querySelectorAll('.team4all-contact-group'));
    const setVisible = (element, visible) => {
        element.hidden = !visible;
        element.style.display = visible ? '' : 'none';
    };

    const applySearch = () => {
        const query = search.value.trim().toLowerCase();

        groups.forEach((group) => {
            const items = Array.from(group.querySelectorAll('[data-team4all-contact-search]'));
            let hasVisibleItems = false;

            items.forEach((item) => {
                const haystack = (item.getAttribute('data-team4all-contact-search') || '').toLowerCase();
                const visible = query === '' || haystack.includes(query);
                setVisible(item, visible);

                if (visible) {
                    hasVisibleItems = true;
                }
            });

            const placeholder = group.querySelector('.team4all-contact-placeholder');
            if (placeholder) {
                setVisible(placeholder, query === '');
                if (query === '') {
                    hasVisibleItems = true;
                }
            }

            setVisible(group, hasVisibleItems);
        });
    };

    search.addEventListener('input', applySearch);
    search.addEventListener('change', applySearch);
    search.addEventListener('search', applySearch);
    applySearch();
})();
