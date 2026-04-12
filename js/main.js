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

    const groups = Array.from(root.querySelectorAll('[data-team4all-search]'));

    const applySearch = () => {
        const query = search.value.trim().toLowerCase();

        groups.forEach((group) => {
            const haystack = (group.getAttribute('data-team4all-search') || '').toLowerCase();
            const visible = query === '' || haystack.includes(query);
            group.hidden = !visible;
        });
    };

    search.addEventListener('input', applySearch);
})();
