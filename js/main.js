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

    const detailEditors = {
        single: {
            container: detailsSingleEditor,
            title: detailsSingleTitle,
            fields: {
                prefix: document.getElementById('team4all-details-single-prefix'),
                firstName: document.getElementById('team4all-details-single-first-name'),
                lastName: document.getElementById('team4all-details-single-last-name'),
                streetAddress: document.getElementById('team4all-details-single-street-address'),
                postalCode: document.getElementById('team4all-details-single-postal-code'),
                locality: document.getElementById('team4all-details-single-locality'),
                telephones: document.getElementById('team4all-details-single-telephones'),
                emails: document.getElementById('team4all-details-single-emails'),
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
        company: trigger.getAttribute(`${prefix}-company`) || '',
        uri: trigger.getAttribute(`${prefix}-uri`) || '',
        prefix: decodeDataValue(trigger.getAttribute(`${prefix}-prefix`) || ''),
        firstName: decodeDataValue(trigger.getAttribute(`${prefix}-first-name`) || ''),
        lastName: decodeDataValue(trigger.getAttribute(`${prefix}-last-name`) || ''),
        addressType: trigger.getAttribute(`${prefix}-address-type`) || 'work',
        streetAddress: decodeDataValue(trigger.getAttribute(`${prefix}-street-address`) || ''),
        postalCode: decodeDataValue(trigger.getAttribute(`${prefix}-postal-code`) || ''),
        locality: decodeDataValue(trigger.getAttribute(`${prefix}-locality`) || ''),
        telephones: decodeDataValue(trigger.getAttribute(`${prefix}-telephones`) || ''),
        telephoneEntries: (() => {
            const encoded = trigger.getAttribute(`${prefix}-telephone-entries`) || '';

            if (encoded === '') {
                return [];
            }

            try {
                const parsed = JSON.parse(decodeDataValue(encoded));
                return Array.isArray(parsed) ? parsed : [];
            } catch (error) {
                return [];
            }
        })(),
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
            addressType: data.addressType || 'work',
            streetAddress: data.streetAddress || '',
            postalCode: data.postalCode || '',
            locality: data.locality || '',
            telephones: data.telephones || '',
            telephoneEntries: data.telephoneEntries || [],
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
        ['prefix', 'firstName', 'lastName', 'streetAddress', 'postalCode', 'locality'].forEach((fieldName) => {
            const field = editor.fields[fieldName];
            if (field && 'value' in field) {
                field.value = '';
            }
        });
        renderTelephoneEntries(editor.fields.telephones, []);
        renderMailLinks(editor.fields.emails, []);
    };

    const buildMailHref = (email) => `${window.OC?.webroot || ''}/apps/mail/compose?to=${encodeURIComponent(email)}`;

    const renderTelephoneEntries = (element, entries) => {
        if (!element) {
            return;
        }

        element.replaceChildren();

        if (!entries.length) {
            const empty = document.createElement('span');
            empty.className = 'team4all-details__link-empty';
            empty.textContent = 'Keine Telefonnummer vorhanden.';
            element.appendChild(empty);
            return;
        }

        entries.forEach((entry) => {
            const row = document.createElement('div');
            row.className = 'team4all-details__telephone-entry';

            const label = document.createElement('span');
            label.className = 'team4all-details__telephone-label';
            label.textContent = entry.label || 'Telefon';

            const link = document.createElement('a');
            link.className = 'team4all-details__link';
            link.textContent = entry.value || '';
            link.href = `tel:${entry.value || ''}`;

            row.append(label, link);
            element.appendChild(row);
        });
    };

    const renderMailLinks = (element, values) => {
        if (!element) {
            return;
        }

        element.replaceChildren();

        if (!values.length) {
            const empty = document.createElement('span');
            empty.className = 'team4all-details__link-empty';
            empty.textContent = 'Keine E-Mail-Adresse vorhanden.';
            element.appendChild(empty);
            return;
        }

        values.forEach((value) => {
            const link = document.createElement('a');
            link.className = 'team4all-details__link';
            link.textContent = `mailto:${value}`;
            link.href = buildMailHref(value);
            link.dataset.mailto = `mailto:${value}`;
            element.appendChild(link);
        });
    };

    const splitMultilineValue = (value) => value.split(/\r\n|\r|\n/).map((entry) => entry.trim()).filter((entry) => entry !== '');

    const buildDetailTitle = (title, company) => {
        const trimmedTitle = (title || '').trim();
        const trimmedCompany = (company || '').trim();

        if (trimmedTitle === '') {
            return trimmedCompany;
        }

        if (trimmedCompany === '') {
            return trimmedTitle;
        }

        return `${trimmedTitle} (${trimmedCompany})`;
    };

    const populateDetailEditor = (editor, title, data) => {
        if (!editor) {
            return;
        }

        setContent(editor.title, buildDetailTitle(title, data.company), 'Kontaktdaten');
        editor.fields.prefix.value = data.prefix || '';
        editor.fields.firstName.value = data.firstName || '';
        editor.fields.lastName.value = data.lastName || '';
        editor.fields.streetAddress.value = data.streetAddress || '';
        editor.fields.postalCode.value = data.postalCode || '';
        editor.fields.locality.value = data.locality || '';
        renderTelephoneEntries(editor.fields.telephones, data.telephoneEntries || []);
        renderMailLinks(editor.fields.emails, splitMultilineValue(data.emails || ''));
        assignDetailEditorState(editor, data);
    };

    const readDetailEditorValues = (editor) => ({
        prefix: editor.fields.prefix.value || '',
        firstName: editor.fields.firstName.value || '',
        lastName: editor.fields.lastName.value || '',
        streetAddress: editor.fields.streetAddress.value || '',
        postalCode: editor.fields.postalCode.value || '',
        locality: editor.fields.locality.value || '',
        addressType: JSON.parse(editor.container.dataset.originalValue || '{}').addressType || 'work',
        telephones: JSON.parse(editor.container.dataset.originalValue || '{}').telephones || '',
        telephoneEntries: JSON.parse(editor.container.dataset.originalValue || '{}').telephoneEntries || [],
        emails: JSON.parse(editor.container.dataset.originalValue || '{}').emails || '',
    });

    const restoreDetailEditorValues = (editor, values) => {
        editor.fields.prefix.value = values.prefix || '';
        editor.fields.firstName.value = values.firstName || '';
        editor.fields.lastName.value = values.lastName || '';
        editor.fields.streetAddress.value = values.streetAddress || '';
        editor.fields.postalCode.value = values.postalCode || '';
        editor.fields.locality.value = values.locality || '';
        renderTelephoneEntries(editor.fields.telephones, values.telephoneEntries || []);
        renderMailLinks(editor.fields.emails, splitMultilineValue(values.emails || ''));
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
                addressType: values.addressType,
                streetAddress: values.streetAddress,
                postalCode: values.postalCode,
                locality: values.locality,
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
            if (!field || !('addEventListener' in field) || !('value' in field)) {
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
    };

    const showSingleDetails = (title, data) => {
        setVisible(detailsEmpty, false);
        setVisible(detailsSingle, true);
        populateDetailEditor(detailEditors.single, title, data);
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

    const activateTrigger = (trigger) => {
            const noteMode = trigger.getAttribute('data-team4all-note-mode') || 'single';
            showSingleDetails(
                trigger.getAttribute('data-team4all-detail-title') || '',
                readTriggerDetailData(trigger)
            );

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
    };

    triggers.forEach((trigger) => {
        trigger.addEventListener('click', () => {
            activateTrigger(trigger);
        });

        trigger.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter' && event.key !== ' ') {
                return;
            }

            event.preventDefault();
            activateTrigger(trigger);
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
})();
