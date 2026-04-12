(function () {
    const root = document.getElementById('team4all-root');
    if (!root) {
        return;
    }

    root.dataset.initialized = 'true';

    const search = document.getElementById('team4all-contact-search');
    const groups = Array.from(root.querySelectorAll('.team4all-contact-group'));
    const triggers = Array.from(root.querySelectorAll('.team4all-contact-trigger'));
    const requestToken = document.head?.dataset?.requesttoken || '';
    const noteSaveUrl = `${window.OC?.webroot || ''}/apps/team4all/note`;
    const contactSaveUrl = `${window.OC?.webroot || ''}/apps/team4all/contact`;

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

    const detailsEmpty = document.getElementById('team4all-details-empty');
    const detailsSingle = document.getElementById('team4all-details-single');
    const detailsSingleEditor = document.getElementById('team4all-details-single-editor');
    const detailsSingleTitle = document.getElementById('team4all-details-single-title');
    const detailsSplit = document.getElementById('team4all-details-split');
    const detailsLeaderEditor = document.getElementById('team4all-details-leader-editor');
    const detailsLeaderTitle = document.getElementById('team4all-details-leader-title');
    const detailsMemberEditor = document.getElementById('team4all-details-member-editor');
    const detailsMemberTitle = document.getElementById('team4all-details-member-title');

    const detailEditors = {
        single: {
            container: detailsSingleEditor,
            title: detailsSingleTitle,
            fields: {
                prefix: document.getElementById('team4all-details-single-prefix'),
                firstName: document.getElementById('team4all-details-single-first-name'),
                lastName: document.getElementById('team4all-details-single-last-name'),
                address: document.getElementById('team4all-details-single-address'),
                telephones: document.getElementById('team4all-details-single-telephones'),
                emails: document.getElementById('team4all-details-single-emails'),
            },
        },
        leader: {
            container: detailsLeaderEditor,
            title: detailsLeaderTitle,
            fields: {
                prefix: document.getElementById('team4all-details-leader-prefix'),
                firstName: document.getElementById('team4all-details-leader-first-name'),
                lastName: document.getElementById('team4all-details-leader-last-name'),
                address: document.getElementById('team4all-details-leader-address'),
                telephones: document.getElementById('team4all-details-leader-telephones'),
                emails: document.getElementById('team4all-details-leader-emails'),
            },
        },
        member: {
            container: detailsMemberEditor,
            title: detailsMemberTitle,
            fields: {
                prefix: document.getElementById('team4all-details-member-prefix'),
                firstName: document.getElementById('team4all-details-member-first-name'),
                lastName: document.getElementById('team4all-details-member-last-name'),
                address: document.getElementById('team4all-details-member-address'),
                telephones: document.getElementById('team4all-details-member-telephones'),
                emails: document.getElementById('team4all-details-member-emails'),
            },
        },
    };

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

    const readTriggerDetailData = (trigger, prefix = 'data-team4all-detail') => ({
        title: trigger.getAttribute(`${prefix}-title`) || '',
        uri: trigger.getAttribute(`${prefix}-uri`) || '',
        prefix: decodeDataValue(trigger.getAttribute(`${prefix}-prefix`) || ''),
        firstName: decodeDataValue(trigger.getAttribute(`${prefix}-first-name`) || ''),
        lastName: decodeDataValue(trigger.getAttribute(`${prefix}-last-name`) || ''),
        address: decodeDataValue(trigger.getAttribute(`${prefix}-address`) || ''),
        telephones: decodeDataValue(trigger.getAttribute(`${prefix}-telephones`) || ''),
        emails: decodeDataValue(trigger.getAttribute(`${prefix}-emails`) || ''),
    });

    const showEmptyNotes = () => {
        setVisible(notesEmpty, true);
        setVisible(notesSingle, false);
        setVisible(notesSplit, false);
    };

    const assignNoteEditorState = (element, uri, value) => {
        if (!element) {
            return;
        }

        element.dataset.noteUri = uri || '';
        element.dataset.originalValue = value || '';
        element.dataset.saving = 'false';
    };

    const clearNoteEditorState = (element) => {
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

        const confirmed = window.confirm('Änderungen an der Notiz speichern?');
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

            const response = await fetch(noteSaveUrl, {
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

    const registerNoteEditor = (element) => {
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

    const assignDetailEditorState = (editor, data) => {
        if (!editor?.container) {
            return;
        }

        editor.container.dataset.contactUri = data.uri || '';
        editor.container.dataset.originalValue = JSON.stringify({
            prefix: data.prefix || '',
            firstName: data.firstName || '',
            lastName: data.lastName || '',
            address: data.address || '',
            telephones: data.telephones || '',
            emails: data.emails || '',
        });
        editor.container.dataset.saving = 'false';
    };

    const clearDetailEditorState = (editor) => {
        if (!editor?.container) {
            return;
        }

        editor.container.dataset.contactUri = '';
        editor.container.dataset.originalValue = '';
        editor.container.dataset.saving = 'false';
        Object.values(editor.fields).forEach((field) => {
            if (field) {
                field.value = '';
            }
        });
    };

    const populateDetailEditor = (editor, title, data) => {
        if (!editor) {
            return;
        }

        setContent(editor.title, title, 'Kontaktdaten');
        editor.fields.prefix.value = data.prefix || '';
        editor.fields.firstName.value = data.firstName || '';
        editor.fields.lastName.value = data.lastName || '';
        editor.fields.address.value = data.address || '';
        editor.fields.telephones.value = data.telephones || '';
        editor.fields.emails.value = data.emails || '';
        assignDetailEditorState(editor, data);
    };

    const readDetailEditorValues = (editor) => ({
        prefix: editor.fields.prefix.value || '',
        firstName: editor.fields.firstName.value || '',
        lastName: editor.fields.lastName.value || '',
        address: editor.fields.address.value || '',
        telephones: editor.fields.telephones.value || '',
        emails: editor.fields.emails.value || '',
    });

    const restoreDetailEditorValues = (editor, values) => {
        editor.fields.prefix.value = values.prefix || '';
        editor.fields.firstName.value = values.firstName || '';
        editor.fields.lastName.value = values.lastName || '';
        editor.fields.address.value = values.address || '';
        editor.fields.telephones.value = values.telephones || '';
        editor.fields.emails.value = values.emails || '';
    };

    const saveDetailEditor = async (editor) => {
        if (!editor?.container) {
            return;
        }

        const uri = editor.container.dataset.contactUri || '';
        const originalValue = editor.container.dataset.originalValue || '';
        const currentValue = JSON.stringify(readDetailEditorValues(editor));

        if (uri === '' || currentValue === originalValue || editor.container.dataset.saving === 'true') {
            return;
        }

        const confirmed = window.confirm('Änderungen an den Kontaktdaten speichern?');
        if (!confirmed) {
            restoreDetailEditorValues(editor, JSON.parse(originalValue || '{}'));
            return;
        }

        editor.container.dataset.saving = 'true';

        try {
            const values = readDetailEditorValues(editor);
            const body = new URLSearchParams({
                uri,
                prefix: values.prefix,
                firstName: values.firstName,
                lastName: values.lastName,
                address: values.address,
                telephones: values.telephones,
                emails: values.emails,
            });

            const response = await fetch(contactSaveUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    requesttoken: requestToken,
                },
                body: body.toString(),
            });

            if (!response.ok) {
                throw new Error(`Saving contact failed with status ${response.status}`);
            }

            editor.container.dataset.originalValue = JSON.stringify(values);
        } catch (error) {
            console.error(error);
            restoreDetailEditorValues(editor, JSON.parse(originalValue || '{}'));
        } finally {
            editor.container.dataset.saving = 'false';
        }
    };

    const registerDetailEditor = (editor) => {
        if (!editor?.container) {
            return;
        }

        Object.values(editor.fields).forEach((field) => {
            if (!field) {
                return;
            }

            field.addEventListener('blur', () => {
                window.setTimeout(() => {
                    if (editor.container.contains(document.activeElement)) {
                        return;
                    }

                    void saveDetailEditor(editor);
                }, 0);
            });

            field.addEventListener('keydown', (event) => {
                if (event.key !== 'Escape') {
                    return;
                }

                event.preventDefault();
                restoreDetailEditorValues(editor, JSON.parse(editor.container.dataset.originalValue || '{}'));
                field.blur();
            });
        });
    };

    const showEmptyDetails = () => {
        setVisible(detailsEmpty, true);
        setVisible(detailsSingle, false);
        setVisible(detailsSplit, false);
    };

    const showSingleDetails = (title, data) => {
        setVisible(detailsEmpty, false);
        setVisible(detailsSingle, true);
        setVisible(detailsSplit, false);
        populateDetailEditor(detailEditors.single, title, data);
        clearDetailEditorState(detailEditors.leader);
        clearDetailEditorState(detailEditors.member);
    };

    const showLeaderDetails = (leaderTitle, leaderData) => {
        setVisible(detailsEmpty, false);
        setVisible(detailsSingle, false);
        setVisible(detailsSplit, true);
        setVisible(detailsMemberEditor, false);
        populateDetailEditor(detailEditors.leader, leaderTitle, leaderData);
        clearDetailEditorState(detailEditors.member);
    };

    const showMemberDetails = (leaderTitle, leaderData, memberTitle, memberData) => {
        setVisible(detailsEmpty, false);
        setVisible(detailsSingle, false);
        setVisible(detailsSplit, true);
        setVisible(detailsMemberEditor, true);
        populateDetailEditor(detailEditors.leader, leaderTitle, leaderData);
        populateDetailEditor(detailEditors.member, memberTitle, memberData);
    };

    const showSingleNote = (title, uri, content) => {
        setVisible(notesEmpty, false);
        setVisible(notesSingle, true);
        setVisible(notesSplit, false);
        setContent(notesSingleTitle, title, 'Notiz');
        setContent(notesSingleContent, content, 'Keine Notiz vorhanden.');
        assignNoteEditorState(notesSingleContent, uri, content);
        clearNoteEditorState(notesLeaderContent);
        clearNoteEditorState(notesMemberContent);
    };

    const showLeaderNote = (leaderTitle, leaderUri, leaderContent) => {
        setVisible(notesEmpty, false);
        setVisible(notesSingle, false);
        setVisible(notesSplit, true);
        setVisible(notesMemberSection, false);
        setContent(notesLeaderTitle, leaderTitle, 'Leader');
        setContent(notesLeaderContent, leaderContent, 'Keine Notiz vorhanden.');
        assignNoteEditorState(notesLeaderContent, leaderUri, leaderContent);
        setContent(notesMemberTitle, '', '');
        clearNoteEditorState(notesMemberContent);
    };

    const showMemberNote = (leaderTitle, leaderUri, leaderContent, memberTitle, memberUri, memberContent) => {
        setVisible(notesEmpty, false);
        setVisible(notesSingle, false);
        setVisible(notesSplit, true);
        setVisible(notesMemberSection, true);
        setContent(notesLeaderTitle, leaderTitle, 'Leader');
        setContent(notesLeaderContent, leaderContent, 'Keine Notiz vorhanden.');
        assignNoteEditorState(notesLeaderContent, leaderUri, leaderContent);
        setContent(notesMemberTitle, memberTitle, 'Notiz');
        setContent(notesMemberContent, memberContent, 'Keine Notiz vorhanden.');
        assignNoteEditorState(notesMemberContent, memberUri, memberContent);
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
            const noteMode = trigger.getAttribute('data-team4all-note-mode') || 'single';
            const detailMode = trigger.getAttribute('data-team4all-detail-mode') || noteMode;

            if (detailMode === 'leader') {
                showLeaderDetails(
                    trigger.getAttribute('data-team4all-detail-title') || trigger.getAttribute('data-team4all-leader-detail-title') || '',
                    readTriggerDetailData(trigger)
                );
            } else if (detailMode === 'member') {
                showMemberDetails(
                    trigger.getAttribute('data-team4all-leader-detail-title') || '',
                    readTriggerDetailData(trigger, 'data-team4all-leader-detail'),
                    trigger.getAttribute('data-team4all-detail-title') || '',
                    readTriggerDetailData(trigger)
                );
            } else {
                showSingleDetails(
                    trigger.getAttribute('data-team4all-detail-title') || '',
                    readTriggerDetailData(trigger)
                );
            }

            if (noteMode === 'leader') {
                showLeaderNote(
                    trigger.getAttribute('data-team4all-note-title') || trigger.getAttribute('data-team4all-leader-title') || '',
                    trigger.getAttribute('data-team4all-note-uri') || trigger.getAttribute('data-team4all-leader-uri') || '',
                    decodeDataValue(trigger.getAttribute('data-team4all-note-content') || trigger.getAttribute('data-team4all-leader-content') || '')
                );
                return;
            }

            if (noteMode === 'member') {
                showMemberNote(
                    trigger.getAttribute('data-team4all-leader-title') || '',
                    trigger.getAttribute('data-team4all-leader-uri') || '',
                    decodeDataValue(trigger.getAttribute('data-team4all-leader-content') || ''),
                    trigger.getAttribute('data-team4all-note-title') || '',
                    trigger.getAttribute('data-team4all-note-uri') || '',
                    decodeDataValue(trigger.getAttribute('data-team4all-note-content') || '')
                );
                return;
            }

            showSingleNote(
                trigger.getAttribute('data-team4all-note-title') || '',
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

    showEmptyDetails();
    showEmptyNotes();
    registerNoteEditor(notesSingleContent);
    registerNoteEditor(notesLeaderContent);
    registerNoteEditor(notesMemberContent);
    registerDetailEditor(detailEditors.single);
    registerDetailEditor(detailEditors.leader);
    registerDetailEditor(detailEditors.member);
})();
