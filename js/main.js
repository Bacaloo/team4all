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

    const applySearch = () => {
        const query = search.value.trim().toLowerCase();

        groups.forEach((group) => {
            const items = Array.from(group.querySelectorAll('[data-team4all-contact-search]'));
            let hasVisibleItems = false;

            items.forEach((item) => {
                const haystack = (item.getAttribute('data-team4all-contact-search') || '').toLowerCase();
                const visible = query === '' || haystack.includes(query);
                item.hidden = !visible;

                if (visible) {
                    hasVisibleItems = true;
                }
            });

            const placeholder = group.querySelector('.team4all-contact-placeholder');
            if (placeholder) {
                placeholder.hidden = query !== '';
                if (query === '') {
                    hasVisibleItems = true;
                }
            }

            group.hidden = !hasVisibleItems;
        });
    };

    search.addEventListener('input', applySearch);
})();
