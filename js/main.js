(function () {
    const root = document.getElementById('team4all-root');
    if (!root) {
        return;
    }

    root.dataset.initialized = 'true';

    const search = document.getElementById('team4all-contact-search');
    const groups = Array.from(root.querySelectorAll('.team4all-contact-group'));
    const triggers = Array.from(root.querySelectorAll('.team4all-contact-trigger'));

    const notesEmpty = document.getElementById('team4all-notes-empty');
    const notesSingle = document.getElementById('team4all-notes-single');
    const notesSingleTitle = document.getElementById('team4all-notes-single-title');
    const notesSingleContent = document.getElementById('team4all-notes-single-content');
    const notesSplit = document.getElementById('team4all-notes-split');
    const notesLeaderTitle = document.getElementById('team4all-notes-leader-title');
    const notesLeaderContent = document.getElementById('team4all-notes-leader-content');
    const notesMemberSection = document.getElementById('team4all-notes-member-section');
    const notesMemberTitle = document.getElementById('team4all-notes-member-title');
    const notesMemberContent = document.getElementById('team4all-notes-member-content');

    const setVisible = (element, visible) => {
        if (!element) {
            return;
        }

        element.hidden = !visible;
        element.style.display = visible ? '' : 'none';
    };

    const setContent = (element, value, fallback) => {
        if (!element) {
            return;
        }

        element.textContent = value && value.trim() !== '' ? value : fallback;
    };

    const showEmptyNotes = () => {
        setVisible(notesEmpty, true);
        setVisible(notesSingle, false);
        setVisible(notesSplit, false);
    };

    const showSingleNote = (title, content) => {
        setVisible(notesEmpty, false);
        setVisible(notesSingle, true);
        setVisible(notesSplit, false);
        setContent(notesSingleTitle, title, 'Notiz');
        setContent(notesSingleContent, content, 'Keine Notiz vorhanden.');
    };

    const showLeaderNote = (leaderTitle, leaderContent) => {
        setVisible(notesEmpty, false);
        setVisible(notesSingle, false);
        setVisible(notesSplit, true);
        setVisible(notesMemberSection, false);
        setContent(notesLeaderTitle, leaderTitle, 'Leader');
        setContent(notesLeaderContent, leaderContent, 'Keine Notiz vorhanden.');
        setContent(notesMemberTitle, '', '');
        setContent(notesMemberContent, '', '');
    };

    const showMemberNote = (leaderTitle, leaderContent, memberTitle, memberContent) => {
        setVisible(notesEmpty, false);
        setVisible(notesSingle, false);
        setVisible(notesSplit, true);
        setVisible(notesMemberSection, true);
        setContent(notesLeaderTitle, leaderTitle, 'Leader');
        setContent(notesLeaderContent, leaderContent, 'Keine Notiz vorhanden.');
        setContent(notesMemberTitle, memberTitle, 'Notiz');
        setContent(notesMemberContent, memberContent, 'Keine Notiz vorhanden.');
    };

    const applySearch = () => {
        if (!search) {
            return;
        }

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

    triggers.forEach((trigger) => {
        trigger.addEventListener('click', () => {
            const mode = trigger.getAttribute('data-team4all-note-mode') || 'single';

            if (mode === 'leader') {
                showLeaderNote(
                    trigger.getAttribute('data-team4all-leader-title') || '',
                    trigger.getAttribute('data-team4all-leader-content') || ''
                );
                return;
            }

            if (mode === 'member') {
                showMemberNote(
                    trigger.getAttribute('data-team4all-leader-title') || '',
                    trigger.getAttribute('data-team4all-leader-content') || '',
                    trigger.getAttribute('data-team4all-note-title') || '',
                    trigger.getAttribute('data-team4all-note-content') || ''
                );
                return;
            }

            showSingleNote(
                trigger.getAttribute('data-team4all-note-title') || '',
                trigger.getAttribute('data-team4all-note-content') || ''
            );
        });
    });

    if (search) {
        search.addEventListener('input', applySearch);
        search.addEventListener('change', applySearch);
        search.addEventListener('search', applySearch);
        applySearch();
    }

    showEmptyNotes();
})();
