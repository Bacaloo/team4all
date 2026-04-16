(function () {
    const root = document.getElementById('team4all-root');
    if (!root) {
        return;
    }

    root.dataset.initialized = 'true';

    const iconUrl = root.dataset.team4allIconUrl || '';

    if (iconUrl !== '' && document.head) {
        [
            { rel: 'icon', type: 'image/svg+xml', href: iconUrl },
            { rel: 'shortcut icon', type: 'image/svg+xml', href: iconUrl },
            { rel: 'apple-touch-icon', type: '', href: iconUrl },
        ].forEach(({ rel, type }) => {
            let link = document.head.querySelector(`link[rel="${rel}"]`);
            if (!link) {
                link = document.createElement('link');
                link.rel = rel;
                document.head.appendChild(link);
            }

            link.href = iconUrl;

            if (type !== '') {
                link.type = type;
            } else {
                link.removeAttribute('type');
            }
        });
    }

    const search = document.getElementById('team4all-contact-search');
    const contactList = root.querySelector('.team4all-contact-list');
    const filterChips = Array.from(root.querySelectorAll('.team4all-filter-chip'));
    const requestToken = document.head?.dataset?.requesttoken || '';
    const noteSaveUrl = `${window.OC?.webroot || ''}/apps/team4all/note`;
    const contactSaveUrl = `${window.OC?.webroot || ''}/apps/team4all/contact`;
    const contactFetchUrl = `${window.OC?.webroot || ''}/apps/team4all/contact/fetch`;
    const contactMetaUrl = `${window.OC?.webroot || ''}/apps/team4all/contact-meta`;
    const contactListRefreshUrl = window.location.href;
    const groupMoveUrl = root.dataset.team4allGroupMoveUrl || '';
    const groupVCardUrl = root.dataset.team4allGroupVcardUrl || '';
    const groupMenu = document.getElementById('team4all-group-menu');
    const groupMoveDialog = document.getElementById('team4all-group-move-dialog');
    const groupMoveDialogLabel = document.getElementById('team4all-group-move-dialog-label');
    const groupMoveTarget = document.getElementById('team4all-group-move-target');

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
    const detailsSingleCompany = document.getElementById('team4all-details-single-company');
    const detailsSingleAddressOrigin = document.getElementById('team4all-details-single-address-origin');

    const detailEditors = {
        single: {
            container: detailsSingleEditor,
            title: detailsSingleTitle,
            company: detailsSingleCompany,
            addressOrigin: detailsSingleAddressOrigin,
            fields: {
                anrede: document.getElementById('team4all-details-single-anrede'),
                briefanrede: document.getElementById('team4all-details-single-briefanrede'),
                prefix: document.getElementById('team4all-details-single-prefix'),
                firstName: document.getElementById('team4all-details-single-first-name'),
                lastName: document.getElementById('team4all-details-single-last-name'),
                streetAddress: document.getElementById('team4all-details-single-street-address'),
                postalCode: document.getElementById('team4all-details-single-postal-code'),
                locality: document.getElementById('team4all-details-single-locality'),
                addresses: document.getElementById('team4all-details-single-addresses'),
                telephones: document.getElementById('team4all-details-single-telephones'),
                emails: document.getElementById('team4all-details-single-emails'),
                contactGroups: document.getElementById('team4all-details-single-contact-groups'),
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

    const activeFilterGroups = new Set();
    let activeGroupMenuState = null;

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

    const movableAddressBooks = (() => {
        const encoded = root.dataset.team4allMovableAddressBooks || '';
        if (!encoded) {
            return [];
        }

        try {
            const parsed = JSON.parse(decodeDataValue(encoded));
            return Array.isArray(parsed) ? parsed : [];
        } catch (error) {
            return [];
        }
    })();

    const readContactGroups = (element) => {
        if (!element) {
            return [];
        }

        const encoded = element.getAttribute('data-team4all-contact-groups') || '';
        if (encoded === '') {
            return [];
        }

        try {
            const parsed = JSON.parse(decodeDataValue(encoded));
            if (!Array.isArray(parsed)) {
                return [];
            }

            return parsed
                .filter((value) => typeof value === 'string')
                .map((value) => value.trim().toLowerCase())
                .filter((value) => value !== '');
        } catch (error) {
            return [];
        }
    };

    const hideGroupMenu = () => {
        if (!groupMenu) {
            return;
        }

        groupMenu.hidden = true;
        groupMenu.style.left = '';
        groupMenu.style.top = '';
    };

    const closeMoveDialog = () => {
        if (!groupMoveDialog) {
            return;
        }

        groupMoveDialog.hidden = true;
    };

    const openGroupMenu = (state, positionX, positionY) => {
        if (!groupMenu) {
            return;
        }

        activeGroupMenuState = state;
        groupMenu.hidden = false;
        groupMenu.style.left = `${positionX}px`;
        groupMenu.style.top = `${positionY}px`;
    };

    const openMoveDialog = () => {
        if (!groupMoveDialog || !groupMoveTarget || !activeGroupMenuState) {
            return;
        }

        const currentAddressBookId = String(activeGroupMenuState.addressBookId || '');
        const options = movableAddressBooks.filter((addressBook) => String(addressBook.id || '') !== currentAddressBookId);

        groupMoveTarget.replaceChildren();

        options.forEach((addressBook) => {
            const option = document.createElement('option');
            option.value = String(addressBook.id || '');
            option.textContent = addressBook.label || addressBook.id || '';
            groupMoveTarget.appendChild(option);
        });

        if (groupMoveDialogLabel) {
            groupMoveDialogLabel.textContent = activeGroupMenuState.company || '';
        }

        if (options.length === 0) {
            const option = document.createElement('option');
            option.value = '';
            option.textContent = 'Kein anderes Adressbuch verfuegbar';
            groupMoveTarget.appendChild(option);
        }

        groupMoveDialog.hidden = false;
    };

    const downloadGroupVCard = () => {
        if (!activeGroupMenuState || !groupVCardUrl) {
            return;
        }

        const url = new URL(groupVCardUrl, window.location.origin);
        url.searchParams.set('company', activeGroupMenuState.company || '');
        window.location.assign(url.toString());
    };

    const moveGroup = async () => {
        if (!activeGroupMenuState || !groupMoveTarget || !groupMoveUrl) {
            return;
        }

        const targetAddressBookId = (groupMoveTarget.value || '').trim();
        if (targetAddressBookId === '') {
            closeMoveDialog();
            return;
        }

        await saveDetailEditor(detailEditors.single);

        const body = new URLSearchParams({
            company: activeGroupMenuState.company || '',
            targetAddressBookId,
        });

        const response = await fetch(groupMoveUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                requesttoken: requestToken,
            },
            body: body.toString(),
        });

        if (!response.ok) {
            throw new Error(`Moving group failed with status ${response.status}`);
        }

        closeMoveDialog();
        hideGroupMenu();
        await refreshContactList();
    };

    const readTriggerDetailData = (trigger, prefix = 'data-team4all-detail') => ({
        title: trigger.getAttribute(`${prefix}-title`) || '',
        company: trigger.getAttribute(`${prefix}-company`) || '',
        contactUid: trigger.getAttribute(`${prefix}-uid`) || '',
        uri: trigger.getAttribute(`${prefix}-uri`) || '',
        addressBookId: trigger.getAttribute(`${prefix}-address-book-id`) || '',
        prefix: decodeDataValue(trigger.getAttribute(`${prefix}-prefix`) || ''),
        firstName: decodeDataValue(trigger.getAttribute(`${prefix}-first-name`) || ''),
        lastName: decodeDataValue(trigger.getAttribute(`${prefix}-last-name`) || ''),
        addressType: trigger.getAttribute(`${prefix}-address-type`) || 'work',
        streetAddress: decodeDataValue(trigger.getAttribute(`${prefix}-street-address`) || ''),
        postalCode: decodeDataValue(trigger.getAttribute(`${prefix}-postal-code`) || ''),
        locality: decodeDataValue(trigger.getAttribute(`${prefix}-locality`) || ''),
        addresses: (() => {
            const encoded = trigger.getAttribute(`${prefix}-addresses`) || '';

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
        contactGroups: (() => {
            const encoded = trigger.getAttribute(`${prefix}-contact-groups`) || '';

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
    });

    const showEmptyNotes = () => {
        setVisible(notesEmpty, true);
        setVisible(notesSingle, false);
        setVisible(notesSplit, false);
    };

    const assignNoteEditorState = (element, uid, uri, addressBookId, value) => {
        if (!element) {
            return;
        }

        element.dataset.noteUid = uid || '';
        element.dataset.noteUri = uri || '';
        element.dataset.noteAddressBookId = addressBookId || '';
        element.dataset.originalValue = value || '';
        element.dataset.saving = 'false';
    };

    const clearNoteEditorState = (element) => {
        if (!element) {
            return;
        }

        element.dataset.noteUid = '';
        element.dataset.noteUri = '';
        element.dataset.noteAddressBookId = '';
        element.dataset.originalValue = '';
        element.dataset.saving = 'false';
        element.value = '';
    };

    const saveEditorNote = async (element) => {
        if (!element) {
            return;
        }

        const uid = element.dataset.noteUid || '';
        const uri = element.dataset.noteUri || '';
        const addressBookId = element.dataset.noteAddressBookId || '';
        const originalValue = element.dataset.originalValue || '';
        const currentValue = element.value || '';

        if ((uid === '' && uri === '') || currentValue === originalValue || element.dataset.saving === 'true') {
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
                uid,
                uri,
                addressBookId,
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
        editor.container.dataset.contactUid = data.contactUid || '';
        editor.container.dataset.contactAddressBookId = data.addressBookId || '';
        editor.container.dataset.originalValue = JSON.stringify({
            anrede: data.anrede || '',
            briefanrede: data.briefanrede || '',
            prefix: data.prefix || '',
            firstName: data.firstName || '',
            lastName: data.lastName || '',
            addressType: data.addressType || 'work',
            streetAddress: data.streetAddress || '',
            postalCode: data.postalCode || '',
            locality: data.locality || '',
            addresses: data.addresses || [],
            telephones: data.telephones || '',
            telephoneEntries: data.telephoneEntries || [],
            emails: data.emails || '',
            contactGroups: data.contactGroups || [],
        });
        editor.container.dataset.saving = 'false';
        editor.container.dataset.dirty = 'false';
    };

    const clearDetailEditorState = (editor) => {
        if (!editor?.container) {
            return;
        }

        editor.container.dataset.contactUri = '';
        editor.container.dataset.contactUid = '';
        editor.container.dataset.contactAddressBookId = '';
        editor.container.dataset.originalValue = '';
        editor.container.dataset.saving = 'false';
        editor.container.dataset.dirty = 'false';
        ['anrede', 'briefanrede', 'prefix', 'firstName', 'lastName', 'streetAddress', 'postalCode', 'locality'].forEach((fieldName) => {
            const field = editor.fields[fieldName];
            if (field && 'value' in field) {
                field.value = '';
            }
        });
        renderTelephoneEntries(editor.fields.telephones, []);
        renderMailLinks(editor.fields.emails, []);
        renderContactGroups(editor.fields.contactGroups, []);
        renderAddresses(editor.fields.addresses, [], 'work');
        setContent(editor.addressOrigin, '', '');
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
            link.textContent = value;
            link.href = buildMailHref(value);
            link.dataset.mailto = `mailto:${value}`;
            element.appendChild(link);
        });
    };

    const renderContactGroups = (element, values) => {
        if (!element) {
            return;
        }

        element.replaceChildren();

        if (!values.length) {
            const empty = document.createElement('span');
            empty.className = 'team4all-details__link-empty';
            empty.textContent = 'Keine weitere Kontaktgruppe vorhanden.';
            element.appendChild(empty);
            return;
        }

        values.forEach((value) => {
            const item = document.createElement('span');
            item.className = 'team4all-details__group';
            item.textContent = value;
            element.appendChild(item);
        });
    };

    const formatAddressOrigin = (addressType) => {
        const normalized = (addressType || '').trim().toLowerCase();
        const label = normalized === 'home'
            ? 'Privat'
            : normalized === 'other'
                ? 'Andere'
                : 'Arbeit';

        return `Anschrift (${label})`;
    };

    const renderAddresses = (element, addresses, currentType) => {
        if (!element) {
            return;
        }

        element.replaceChildren();

        const visibleAddresses = Array.isArray(addresses) ? addresses.filter((address) => {
            return (address.streetAddress || '').trim() !== ''
                || (address.postalCode || '').trim() !== ''
                || (address.locality || '').trim() !== '';
        }) : [];

        if (visibleAddresses.length <= 1) {
            element.hidden = true;
            return;
        }

        element.hidden = false;

        visibleAddresses.forEach((address) => {
            const row = document.createElement('div');
            row.className = 'team4all-details__address-entry';

            if ((address.type || 'work') === (currentType || 'work')) {
                row.classList.add('team4all-details__address-entry--current');
            }

            const label = document.createElement('span');
            label.className = 'team4all-details__address-label';
            label.textContent = address.label || 'Anschrift';

            const value = document.createElement('span');
            value.className = 'team4all-details__address-value';
            value.textContent = [address.streetAddress || '', address.postalCode || '', address.locality || '']
                .filter((part) => part.trim() !== '')
                .join(', ');

            row.append(label, value);
            element.appendChild(row);
        });
    };

    const fetchContactByIdentity = async ({ uid = '', uri = '', addressBookId = '' }) => {
        if (!uid && !uri) {
            return null;
        }

        const query = new URLSearchParams();
        if (uid) {
            query.set('uid', uid);
        }
        if (uri) {
            query.set('uri', uri);
        }
        if (addressBookId) {
            query.set('addressBookId', addressBookId);
        }

        const response = await fetch(`${contactFetchUrl}?${query.toString()}`, {
            method: 'GET',
            headers: {
                requesttoken: requestToken,
            },
        });

        if (!response.ok) {
            throw new Error(`Fetching contact failed with status ${response.status}`);
        }

        const payload = await response.json();
        if (!payload?.found || !payload.contact) {
            return null;
        }

        return {
            title: payload.contact.name || '',
            company: payload.contact.companyDisplay || payload.contact.company || '',
            contactUid: payload.contact.uid || uid || '',
            uri: payload.contact.uri || '',
            addressBookId: String(payload.contact.addressBookId || addressBookId || ''),
            prefix: payload.contact.prefix || '',
            firstName: payload.contact.firstName || '',
            lastName: payload.contact.lastName || '',
            addressType: payload.contact.addressType || 'work',
            streetAddress: payload.contact.streetAddress || '',
            postalCode: payload.contact.postalCode || '',
            locality: payload.contact.locality || '',
            addresses: Array.isArray(payload.contact.addresses) ? payload.contact.addresses : [],
            telephones: payload.contact.telephones || '',
            telephoneEntries: Array.isArray(payload.contact.telephoneEntries) ? payload.contact.telephoneEntries : [],
            emails: payload.contact.emails || '',
            contactGroups: Array.isArray(payload.contact.contactGroups) ? payload.contact.contactGroups : [],
            note: payload.contact.note || '',
        };
    };

    const fetchContactMeta = async (contactUid) => {
        if (!contactUid) {
            return {
                anrede: '',
                briefanrede: '',
            };
        }

        const response = await fetch(`${contactMetaUrl}?contactUid=${encodeURIComponent(contactUid)}`, {
            method: 'GET',
            headers: {
                requesttoken: requestToken,
            },
        });

        if (!response.ok) {
            throw new Error(`Fetching contact meta failed with status ${response.status}`);
        }

        const payload = await response.json();

        return {
            anrede: payload?.meta?.anrede || '',
            briefanrede: payload?.meta?.briefanrede || '',
        };
    };

    const splitMultilineValue = (value) => value.split(/\r\n|\r|\n/).map((entry) => entry.trim()).filter((entry) => entry !== '');

    const buildDetailTitle = (title, company) => {
        const trimmedTitle = (title || '').trim();
        const trimmedCompany = (company || '').trim();

        if (trimmedTitle === '') {
            return {
                title: '',
                company: trimmedCompany,
            };
        }

        if (trimmedCompany === '') {
            return {
                title: trimmedTitle,
                company: '',
            };
        }

        return {
            title: trimmedTitle,
            company: ` (${trimmedCompany})`,
        };
    };

    const populateDetailEditor = (editor, title, data) => {
        if (!editor) {
            return;
        }

        const detailTitle = buildDetailTitle(title, data.company);
        setContent(editor.title, detailTitle.title, 'Kontaktdaten');
        setContent(editor.company, detailTitle.company, '');
        editor.fields.anrede.value = data.anrede || '';
        editor.fields.briefanrede.value = data.briefanrede || '';
        editor.fields.prefix.value = data.prefix || '';
        editor.fields.firstName.value = data.firstName || '';
        editor.fields.lastName.value = data.lastName || '';
        editor.fields.streetAddress.value = data.streetAddress || '';
        editor.fields.postalCode.value = data.postalCode || '';
        editor.fields.locality.value = data.locality || '';
        renderAddresses(editor.fields.addresses, data.addresses || [], data.addressType);
        renderTelephoneEntries(editor.fields.telephones, data.telephoneEntries || []);
        renderMailLinks(editor.fields.emails, splitMultilineValue(data.emails || ''));
        renderContactGroups(editor.fields.contactGroups, data.contactGroups || []);
        setContent(editor.addressOrigin, formatAddressOrigin(data.addressType), 'Herkunft der Anschrift');
        assignDetailEditorState(editor, data);
    };

    const readDetailEditorValues = (editor) => ({
        anrede: editor.fields.anrede.value || '',
        briefanrede: editor.fields.briefanrede.value || '',
        prefix: editor.fields.prefix.value || '',
        firstName: editor.fields.firstName.value || '',
        lastName: editor.fields.lastName.value || '',
        streetAddress: editor.fields.streetAddress.value || '',
        postalCode: editor.fields.postalCode.value || '',
        locality: editor.fields.locality.value || '',
        addressType: JSON.parse(editor.container.dataset.originalValue || '{}').addressType || 'work',
        addresses: JSON.parse(editor.container.dataset.originalValue || '{}').addresses || [],
        telephones: JSON.parse(editor.container.dataset.originalValue || '{}').telephones || '',
        telephoneEntries: JSON.parse(editor.container.dataset.originalValue || '{}').telephoneEntries || [],
        emails: JSON.parse(editor.container.dataset.originalValue || '{}').emails || '',
        contactGroups: JSON.parse(editor.container.dataset.originalValue || '{}').contactGroups || [],
    });

    const restoreDetailEditorValues = (editor, values) => {
        editor.fields.anrede.value = values.anrede || '';
        editor.fields.briefanrede.value = values.briefanrede || '';
        editor.fields.prefix.value = values.prefix || '';
        editor.fields.firstName.value = values.firstName || '';
        editor.fields.lastName.value = values.lastName || '';
        editor.fields.streetAddress.value = values.streetAddress || '';
        editor.fields.postalCode.value = values.postalCode || '';
        editor.fields.locality.value = values.locality || '';
        renderAddresses(editor.fields.addresses, values.addresses || [], values.addressType);
        renderTelephoneEntries(editor.fields.telephones, values.telephoneEntries || []);
        renderMailLinks(editor.fields.emails, splitMultilineValue(values.emails || ''));
        renderContactGroups(editor.fields.contactGroups, values.contactGroups || []);
        setContent(editor.addressOrigin, formatAddressOrigin(values.addressType), 'Herkunft der Anschrift');
    };

    const saveDetailEditor = async (editor) => {
        if (!editor?.container) {
            return;
        }

        const uri = editor.container.dataset.contactUri || '';
        const contactUid = editor.container.dataset.contactUid || '';
        const addressBookId = editor.container.dataset.contactAddressBookId || '';
        const originalValue = editor.container.dataset.originalValue || '';
        const currentValue = JSON.stringify(readDetailEditorValues(editor));

        if (
            (contactUid === '' && uri === '')
            || editor.container.dataset.dirty !== 'true'
            || currentValue === originalValue
            || editor.container.dataset.saving === 'true'
        ) {
            return;
        }

        const confirmed = window.confirm('Änderungen an den Kontaktdaten speichern?');
        if (!confirmed) {
            restoreDetailEditorValues(editor, JSON.parse(originalValue || '{}'));
            editor.container.dataset.dirty = 'false';
            return;
        }

        editor.container.dataset.saving = 'true';

        try {
            const values = readDetailEditorValues(editor);
            const contactBody = new URLSearchParams({
                uid: contactUid,
                uri,
                addressBookId,
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

            const contactResponse = await fetch(contactSaveUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    requesttoken: requestToken,
                },
                body: contactBody.toString(),
            });

            if (!contactResponse.ok) {
                throw new Error(`Saving contact failed with status ${contactResponse.status}`);
            }

            if (contactUid !== '') {
                const metaBody = new URLSearchParams({
                    contactUid,
                    anrede: values.anrede,
                    briefanrede: values.briefanrede,
                });

                const metaResponse = await fetch(contactMetaUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                        requesttoken: requestToken,
                    },
                    body: metaBody.toString(),
                });

                if (!metaResponse.ok) {
                    throw new Error(`Saving contact meta failed with status ${metaResponse.status}`);
                }
            }

            editor.container.dataset.originalValue = JSON.stringify(values);
            editor.container.dataset.dirty = 'false';
        } catch (error) {
            console.error(error);
            restoreDetailEditorValues(editor, JSON.parse(originalValue || '{}'));
            editor.container.dataset.dirty = 'false';
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

            field.addEventListener('keydown', (event) => {
                if (event.key !== 'Escape') {
                    return;
                }

                event.preventDefault();
                restoreDetailEditorValues(editor, JSON.parse(editor.container.dataset.originalValue || '{}'));
                editor.container.dataset.dirty = 'false';
                field.blur();
            });

            field.addEventListener('input', () => {
                editor.container.dataset.dirty = 'true';
            });

            field.addEventListener('change', () => {
                editor.container.dataset.dirty = 'true';
            });
        });

        editor.container.addEventListener('focusout', () => {
            window.setTimeout(() => {
                if (editor.container.contains(document.activeElement)) {
                    return;
                }

                void saveDetailEditor(editor);
            }, 0);
        });

        window.addEventListener('blur', () => {
            void saveDetailEditor(editor);
        });

        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'hidden') {
                void saveDetailEditor(editor);
            }
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

    const showSingleNote = (title, uid, uri, addressBookId, content) => {
        setVisible(notesEmpty, false);
        setVisible(notesSingle, true);
        setVisible(notesSplit, false);
        setContent(notesSingleTitle, title, 'Notiz');
        setContent(notesSingleContent, content, 'Keine Notiz vorhanden.');
        assignNoteEditorState(notesSingleContent, uid, uri, addressBookId, content);
        clearNoteEditorState(notesLeaderContent);
        clearNoteEditorState(notesMemberContent);
    };

    const showLeaderNote = (leaderTitle, leaderUid, leaderUri, leaderAddressBookId, leaderContent) => {
        setVisible(notesEmpty, false);
        setVisible(notesSingle, false);
        setVisible(notesSplit, true);
        setVisible(notesMemberSection, false);
        setContent(notesLeaderTitle, leaderTitle, 'Leader');
        setContent(notesLeaderContent, leaderContent, 'Keine Notiz vorhanden.');
        assignNoteEditorState(notesLeaderContent, leaderUid, leaderUri, leaderAddressBookId, leaderContent);
        setContent(notesMemberTitle, '', '');
        clearNoteEditorState(notesMemberContent);
    };

    const showMemberNote = (leaderTitle, leaderUid, leaderUri, leaderAddressBookId, leaderContent, memberTitle, memberUid, memberUri, memberAddressBookId, memberContent) => {
        setVisible(notesEmpty, false);
        setVisible(notesSingle, false);
        setVisible(notesSplit, true);
        setVisible(notesMemberSection, true);
        setContent(notesLeaderTitle, leaderTitle, 'Leader');
        setContent(notesLeaderContent, leaderContent, 'Keine Notiz vorhanden.');
        assignNoteEditorState(notesLeaderContent, leaderUid, leaderUri, leaderAddressBookId, leaderContent);
        setContent(notesMemberTitle, memberTitle, 'Notiz');
        setContent(notesMemberContent, memberContent, 'Keine Notiz vorhanden.');
        assignNoteEditorState(notesMemberContent, memberUid, memberUri, memberAddressBookId, memberContent);
    };

    const applySearch = () => {
        if (!search) {
            return;
        }

        const query = search.value.trim().toLowerCase();
        const groups = Array.from(root.querySelectorAll('.team4all-contact-group'));
        const hasActiveGroupFilters = activeFilterGroups.size > 0;

        groups.forEach((group) => {
            const leaderItem = group.querySelector('.team4all-contact-group__header[data-team4all-contact-search]');
            const memberItems = Array.from(group.querySelectorAll('.team4all-contact-item[data-team4all-contact-search]'));
            const leaderMatches = leaderItem
                ? (
                    (query === '' || ((leaderItem.getAttribute('data-team4all-contact-search') || '').toLowerCase().includes(query)))
                    && (
                        !hasActiveGroupFilters
                        || readContactGroups(leaderItem).some((groupName) => activeFilterGroups.has(groupName))
                    )
                )
                : false;
            let hasVisibleItems = false;
            let hasVisibleMembers = false;

            memberItems.forEach((item) => {
                const haystack = (item.getAttribute('data-team4all-contact-search') || '').toLowerCase();
                const visible = (query === '' || haystack.includes(query))
                    && (
                        !hasActiveGroupFilters
                        || readContactGroups(item).some((groupName) => activeFilterGroups.has(groupName))
                    );
                setVisible(item, visible);

                if (visible) {
                    hasVisibleItems = true;
                    hasVisibleMembers = true;
                }
            });

            if (leaderItem) {
                const showLeader = leaderMatches || hasVisibleMembers || (query === '' && !hasActiveGroupFilters);
                setVisible(leaderItem, showLeader);
                if (showLeader) {
                    hasVisibleItems = true;
                }
            }

            const placeholder = group.querySelector('.team4all-contact-placeholder');
            if (placeholder) {
                setVisible(placeholder, query === '' && !hasActiveGroupFilters);
                if (query === '' && !hasActiveGroupFilters) {
                    hasVisibleItems = true;
                }
            }

            setVisible(group, hasVisibleItems);
        });
    };

    const refreshContactList = async () => {
        if (!contactList) {
            return;
        }

        try {
            const response = await fetch(contactListRefreshUrl, {
                method: 'GET',
                headers: {
                    requesttoken: requestToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                throw new Error(`Refreshing contact list failed with status ${response.status}`);
            }

            const html = await response.text();
            const parser = new DOMParser();
            const documentFragment = parser.parseFromString(html, 'text/html');
            const refreshedContactList = documentFragment.querySelector('.team4all-contact-list');

            if (!refreshedContactList) {
                throw new Error('Refreshing contact list failed because no contact list was returned.');
            }

            contactList.replaceChildren(...Array.from(refreshedContactList.childNodes).map((node) => node.cloneNode(true)));
            applySearch();
        } catch (error) {
            console.error(error);
        }
    };

    const activateTrigger = async (trigger) => {
        await saveDetailEditor(detailEditors.single);

        const noteMode = trigger.getAttribute('data-team4all-note-mode') || 'single';
        let detailData = readTriggerDetailData(trigger);
        let noteData = {
            title: trigger.getAttribute('data-team4all-note-title') || '',
            uid: trigger.getAttribute('data-team4all-note-uid') || '',
            uri: trigger.getAttribute('data-team4all-note-uri') || '',
            addressBookId: trigger.getAttribute('data-team4all-note-address-book-id') || '',
            content: decodeDataValue(trigger.getAttribute('data-team4all-note-content') || ''),
        };
        let leaderData = {
            title: trigger.getAttribute('data-team4all-leader-title') || '',
            uid: trigger.getAttribute('data-team4all-leader-uid') || '',
            uri: trigger.getAttribute('data-team4all-leader-uri') || '',
            addressBookId: trigger.getAttribute('data-team4all-leader-address-book-id') || '',
            content: decodeDataValue(trigger.getAttribute('data-team4all-leader-content') || ''),
        };

        try {
            const freshDetail = await fetchContactByIdentity({
                uid: noteData.uid,
                uri: detailData.uri,
                addressBookId: detailData.addressBookId || noteData.addressBookId,
            });
            if (freshDetail !== null) {
                const meta = await fetchContactMeta(freshDetail.contactUid || detailData.contactUid || noteData.uid);
                detailData = freshDetail;
                detailData.anrede = meta.anrede;
                detailData.briefanrede = meta.briefanrede;
                noteData = {
                    title: freshDetail.title,
                    uid: freshDetail.contactUid || trigger.getAttribute('data-team4all-note-uid') || '',
                    uri: freshDetail.uri,
                    addressBookId: freshDetail.addressBookId || noteData.addressBookId,
                    content: freshDetail.note,
                };
            }

            if (noteMode === 'member' || noteMode === 'leader') {
                const freshLeader = await fetchContactByIdentity({
                    uid: leaderData.uid,
                    uri: leaderData.uri || noteData.uri,
                    addressBookId: leaderData.addressBookId || noteData.addressBookId,
                });
                if (freshLeader !== null) {
                    const leaderDetailData = readTriggerDetailData(trigger, 'data-team4all-leader-detail');
                    const leaderMeta = await fetchContactMeta(freshLeader.contactUid || leaderDetailData.contactUid || leaderData.uid);
                    leaderData = {
                        title: freshLeader.title,
                        uid: freshLeader.contactUid || leaderData.uid,
                        uri: freshLeader.uri,
                        addressBookId: freshLeader.addressBookId || leaderData.addressBookId,
                        content: freshLeader.note,
                    };
                    freshLeader.anrede = leaderMeta.anrede;
                    freshLeader.briefanrede = leaderMeta.briefanrede;

                    if (noteMode === 'leader') {
                        detailData = freshLeader;
                        noteData = {
                            title: freshLeader.title,
                            uid: freshLeader.contactUid,
                            uri: freshLeader.uri,
                            addressBookId: freshLeader.addressBookId,
                            content: freshLeader.note,
                        };
                    }
                }
            }
        } catch (error) {
            console.error(error);
        }

        showSingleDetails(detailData.title || trigger.getAttribute('data-team4all-detail-title') || '', detailData);

        if (noteMode === 'leader') {
            showLeaderNote(
                leaderData.title || noteData.title,
                leaderData.uid || noteData.uid,
                leaderData.uri || noteData.uri,
                leaderData.addressBookId || noteData.addressBookId,
                leaderData.content || noteData.content
            );
            return;
        }

        if (noteMode === 'member') {
            showMemberNote(
                leaderData.title,
                leaderData.uid,
                leaderData.uri,
                leaderData.addressBookId,
                leaderData.content,
                noteData.title,
                noteData.uid,
                noteData.uri,
                noteData.addressBookId,
                noteData.content
            );
            return;
        }

        showSingleNote(
            noteData.title,
            noteData.uid,
            noteData.uri,
            noteData.addressBookId,
            noteData.content
        );
    };

    root.addEventListener('click', (event) => {
        const menuAction = event.target instanceof Element ? event.target.closest('[data-team4all-group-action]') : null;
        if (menuAction) {
            event.preventDefault();
            const action = menuAction.getAttribute('data-team4all-group-action') || '';
            hideGroupMenu();

            if (action === 'move') {
                openMoveDialog();
            } else if (action === 'vcard') {
                downloadGroupVCard();
            }
            return;
        }

        const dialogAction = event.target instanceof Element ? event.target.closest('[data-team4all-group-dialog-action]') : null;
        if (dialogAction) {
            event.preventDefault();
            const action = dialogAction.getAttribute('data-team4all-group-dialog-action') || '';

            if (action === 'cancel') {
                closeMoveDialog();
            } else if (action === 'confirm') {
                void moveGroup().catch((error) => {
                    console.error(error);
                    closeMoveDialog();
                });
            }
            return;
        }

        const filterChip = event.target instanceof Element ? event.target.closest('.team4all-filter-chip') : null;
        if (filterChip) {
            const filterGroup = (filterChip.getAttribute('data-team4all-filter-group') || '').trim().toLowerCase();
            if (filterGroup !== '') {
                if (activeFilterGroups.has(filterGroup)) {
                    activeFilterGroups.delete(filterGroup);
                    filterChip.setAttribute('aria-pressed', 'false');
                } else {
                    activeFilterGroups.add(filterGroup);
                    filterChip.setAttribute('aria-pressed', 'true');
                }

                applySearch();
            }
            return;
        }

        hideGroupMenu();

        if (groupMoveDialog && !groupMoveDialog.hidden) {
            const insideDialog = event.target instanceof Element ? event.target.closest('.team4all-group-dialog__surface') : null;
            if (!insideDialog) {
                closeMoveDialog();
            }
        }

        const trigger = event.target instanceof Element ? event.target.closest('.team4all-contact-trigger') : null;
        if (!trigger) {
            return;
        }

        void activateTrigger(trigger);
    });

    root.addEventListener('keydown', (event) => {
        const trigger = event.target instanceof Element ? event.target.closest('.team4all-contact-trigger') : null;
        if (!trigger || (event.key !== 'Enter' && event.key !== ' ')) {
            return;
        }

        event.preventDefault();
        void activateTrigger(trigger);
    });

    root.addEventListener('contextmenu', (event) => {
        const trigger = event.target instanceof Element ? event.target.closest('.team4all-contact-trigger--header') : null;
        if (!trigger) {
            hideGroupMenu();
            return;
        }

        event.preventDefault();

        openGroupMenu({
            company: trigger.getAttribute('data-team4all-group-company') || '',
            addressBookId: trigger.getAttribute('data-team4all-detail-address-book-id') || '',
        }, event.clientX, event.clientY);
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

    window.setInterval(() => {
        if (document.visibilityState === 'hidden') {
            return;
        }

        void refreshContactList();
    }, 30000);
})();
