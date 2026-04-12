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
    const requestToken = document.head?.dataset?.requesttoken || '';
    const saveUrl = `${window.OC?.webroot || ''}/apps/team4all/note`;

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

        if ('value' in element) {
            element.value = value && value.trim() !== '' ? value : '';
            element.placeholder = fallback;
            return;
        }

        element.textContent = value && value.trim() !== '' ? value : fallback;
    };

    const decodeDataValue = (value) => {
        if (!value) {
            return '';
        }

        try {
            const binary = atob(value);
            const bytes = Uint8Array.from(binary, (character) => character.charCodeAt(0));

            if (typeof TextDecoder !== 'undefined') {
                return new TextDecoder('utf-8').decode(bytes);
            }

            let decoded = '';
            bytes.forEach((byte) => {
                decoded += `%${byte.toString(16).padStart(2, '0')}`;
            });

            return decodeURIComponent(decoded);
        } catch (error) {
            return value;
        }
    };

    const showEmptyNotes = () => {
        setVisible(notesEmpty, true);
        setVisible(notesSingle, false);
        setVisible(notesSplit, false);
    };

    const assignEditorState = (element, uri, value) => {
        if (!element) {
            return;
        }

        element.dataset.noteUri = uri || '';
        element.dataset.originalValue = value || '';
        element.dataset.saving = 'false';
    };

    const clearEditorState = (element) => {
        if (!element) {
            return;
        }

        element.dataset.noteUri = '';
        element.dataset.originalValue = '';
        element.dataset.saving = 'false';
        element.value = '';
    };

    const saveEditorNote = async (element) => {
        if (!element) {
            return;
        }

        const uri = element.dataset.noteUri || '';
        const originalValue = element.dataset.originalValue || '';
        const currentValue = element.value || '';

        if (uri === '' || currentValue === originalValue || element.dataset.saving === 'true') {
            return;
        }

        const confirmed = window.confirm('Aenderungen an der Notiz speichern?');
        if (!confirmed) {
            element.value = originalValue;
            return;
        }

        element.dataset.saving = 'true';

        try {
            const body = new URLSearchParams({
                uri,
                note: currentValue,
            });

            const response = await fetch(saveUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    requesttoken: requestToken,
                },
                body: body.toString(),
            });

            if (!response.ok) {
                throw new Error(`Saving note failed with status ${response.status}`);
            }

            element.dataset.originalValue = currentValue;
        } catch (error) {
            console.error(error);
            element.value = originalValue;
        } finally {
            element.dataset.saving = 'false';
        }
    };

    const registerEditor = (element) => {
        if (!element) {
            return;
        }

        element.addEventListener('blur', () => {
            void saveEditorNote(element);
        });

        element.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') {
                return;
            }

            event.preventDefault();
            element.value = element.dataset.originalValue || '';
            element.blur();
        });
    };

    const showSingleNote = (title, uid, uri, content) => {
        setVisible(notesEmpty, false);
        setVisible(notesSingle, true);
        setVisible(notesSplit, false);
        setContent(notesSingleTitle, title, 'Notiz');
        setContent(notesSingleContent, content, 'Keine Notiz vorhanden.');
        assignEditorState(notesSingleContent, uri, content);
        clearEditorState(notesLeaderContent);
        clearEditorState(notesMemberContent);
    };

    const showLeaderNote = (leaderTitle, leaderUid, leaderUri, leaderContent) => {
        setVisible(notesEmpty, false);
        setVisible(notesSingle, false);
        setVisible(notesSplit, true);
        setVisible(notesMemberSection, false);
        setContent(notesLeaderTitle, leaderTitle, 'Leader');
        setContent(notesLeaderContent, leaderContent, 'Keine Notiz vorhanden.');
        assignEditorState(notesLeaderContent, leaderUri, leaderContent);
        setContent(notesMemberTitle, '', '');
        clearEditorState(notesMemberContent);
    };

    const showMemberNote = (leaderTitle, leaderUid, leaderUri, leaderContent, memberTitle, memberUid, memberUri, memberContent) => {
        setVisible(notesEmpty, false);
        setVisible(notesSingle, false);
        setVisible(notesSplit, true);
        setVisible(notesMemberSection, true);
        setContent(notesLeaderTitle, leaderTitle, 'Leader');
        setContent(notesLeaderContent, leaderContent, 'Keine Notiz vorhanden.');
        assignEditorState(notesLeaderContent, leaderUri, leaderContent);
        setContent(notesMemberTitle, memberTitle, 'Notiz');
        setContent(notesMemberContent, memberContent, 'Keine Notiz vorhanden.');
        assignEditorState(notesMemberContent, memberUri, memberContent);
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
                    trigger.getAttribute('data-team4all-note-title') || trigger.getAttribute('data-team4all-leader-title') || '',
                    trigger.getAttribute('data-team4all-note-uid') || trigger.getAttribute('data-team4all-leader-uid') || '',
                    trigger.getAttribute('data-team4all-note-uri') || trigger.getAttribute('data-team4all-leader-uri') || '',
                    decodeDataValue(trigger.getAttribute('data-team4all-note-content') || trigger.getAttribute('data-team4all-leader-content') || '')
                );
                return;
            }

            if (mode === 'member') {
                showMemberNote(
                    trigger.getAttribute('data-team4all-leader-title') || '',
                    trigger.getAttribute('data-team4all-leader-uid') || '',
                    trigger.getAttribute('data-team4all-leader-uri') || '',
                    decodeDataValue(trigger.getAttribute('data-team4all-leader-content') || ''),
                    trigger.getAttribute('data-team4all-note-title') || '',
                    trigger.getAttribute('data-team4all-note-uid') || '',
                    trigger.getAttribute('data-team4all-note-uri') || '',
                    decodeDataValue(trigger.getAttribute('data-team4all-note-content') || '')
                );
                return;
            }

            showSingleNote(
                trigger.getAttribute('data-team4all-note-title') || '',
                trigger.getAttribute('data-team4all-note-uid') || '',
                trigger.getAttribute('data-team4all-note-uri') || '',
                decodeDataValue(trigger.getAttribute('data-team4all-note-content') || '')
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
    registerEditor(notesSingleContent);
    registerEditor(notesLeaderContent);
    registerEditor(notesMemberContent);
})();
