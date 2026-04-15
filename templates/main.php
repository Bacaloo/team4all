<?php

declare(strict_types=1);

script('team4all', 'main');
style('team4all', 'main');

$team4AllFaviconUrl = image_path('team4all', 'favicon.svg');
?>
<div
    id="team4all-root"
    class="team4all-root"
    data-team4all-icon-url="<?= p($team4AllFaviconUrl) ?>"
    style="display:grid;grid-template-columns:20% repeat(3,minmax(0,1fr));grid-template-rows:50px minmax(0,1fr);gap:12px;padding:8px 12px 12px;min-height:calc(100vh - 50px);width:100%;max-width:none;box-sizing:border-box;align-items:stretch;align-content:stretch;"
>
    <section
        class="team4all-toolbar"
        aria-label="Kontaktsuche"
        style="grid-column:1 / -1;display:flex;align-items:center;justify-content:stretch;gap:16px;height:50px;padding:0 20px;border:1px solid rgba(15,23,42,.14);border-radius:20px;background:#fff;box-shadow:0 12px 30px rgba(15,23,42,.10);box-sizing:border-box;"
    >
        <div class="team4all-toolbar__content">
            <label class="team4all-search">
                <span class="team4all-search__icon" aria-hidden="true"></span>
                <input id="team4all-contact-search" type="search" placeholder="Kontakte suchen" autocomplete="off" aria-label="Kontakte suchen" />
            </label>
        </div>
    </section>

    <aside
        class="team4all-sidebar"
        aria-label="Kontaktebereich"
        style="display:flex;flex-direction:column;gap:12px;height:100%;min-height:0;padding:14px 16px 16px;border:1px solid rgba(15,23,42,.14);border-radius:20px;background:#fff;box-shadow:0 12px 30px rgba(15,23,42,.10);box-sizing:border-box;"
    >
        <div class="team4all-sidebar__header">
            <div>
                <h2>Kontakte</h2>
            </div>
        </div>
		<?php $team4AllGroups = $_['team4AllGroups'] ?? []; ?>
		<div class="team4all-contact-list">
			<?php if ($team4AllGroups === []): ?>
				<div class="team4all-contact-placeholder">
					<strong>Keine Kontakte gefunden</strong>
					<span>Fuer die Kontaktgruppe <code>Team4All</code> wurden noch keine Kontakte gefunden.</span>
				</div>
			<?php else: ?>
				<ul class="team4all-contact-groups">
					<?php foreach ($team4AllGroups as $entry): ?>
						<?php if ($entry['type'] === 'person' && $entry['person'] !== null): ?>
							<li class="team4all-contact-group team4all-contact-group--person">
								<span
									role="button"
									tabindex="0"
									class="team4all-contact-item team4all-contact-item--single team4all-contact-trigger team4all-contact-trigger--text"
									data-team4all-contact-search="<?= p(mb_strtolower($entry['person']['searchText'])) ?>"
									data-team4all-note-mode="single"
									data-team4all-note-title="<?= p($entry['person']['name']) ?>"
									data-team4all-note-uid="<?= p($entry['person']['uid']) ?>"
									data-team4all-note-uri="<?= p($entry['person']['uri']) ?>"
									data-team4all-note-address-book-id="<?= p((string)$entry['person']['addressBookId']) ?>"
									data-team4all-note-content="<?= p(base64_encode($entry['person']['note'])) ?>"
									data-team4all-detail-mode="single"
									data-team4all-detail-title="<?= p($entry['person']['name']) ?>"
									data-team4all-detail-company="<?= p($entry['person']['companyDisplay'] ?? $entry['person']['company']) ?>"
									data-team4all-detail-uid="<?= p($entry['person']['uid']) ?>"
									data-team4all-detail-uri="<?= p($entry['person']['uri']) ?>"
									data-team4all-detail-address-book-id="<?= p((string)$entry['person']['addressBookId']) ?>"
									data-team4all-detail-prefix="<?= p(base64_encode($entry['person']['prefix'])) ?>"
									data-team4all-detail-first-name="<?= p(base64_encode($entry['person']['firstName'])) ?>"
									data-team4all-detail-last-name="<?= p(base64_encode($entry['person']['lastName'])) ?>"
									data-team4all-detail-address-type="<?= p($entry['person']['addressType']) ?>"
									data-team4all-detail-street-address="<?= p(base64_encode($entry['person']['streetAddress'])) ?>"
									data-team4all-detail-postal-code="<?= p(base64_encode($entry['person']['postalCode'])) ?>"
									data-team4all-detail-locality="<?= p(base64_encode($entry['person']['locality'])) ?>"
									data-team4all-detail-addresses="<?= p(base64_encode(json_encode($entry['person']['addresses'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]')) ?>"
									data-team4all-detail-telephones="<?= p(base64_encode($entry['person']['telephones'])) ?>"
									data-team4all-detail-telephone-entries="<?= p(base64_encode(json_encode($entry['person']['telephoneEntries'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]')) ?>"
									data-team4all-detail-emails="<?= p(base64_encode($entry['person']['emails'])) ?>"
									data-team4all-detail-contact-groups="<?= p(base64_encode(json_encode($entry['person']['contactGroups'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]')) ?>"
								>
									<strong><?= p($entry['person']['name']) ?></strong>
								</span>
							</li>
							<?php continue; ?>
						<?php endif; ?>
						<li class="team4all-contact-group">
							<?php if ($entry['leader'] !== null): ?>
								<button
									type="button"
									class="team4all-contact-group__header team4all-contact-trigger team4all-contact-trigger--header"
									data-team4all-contact-search="<?= p(mb_strtolower($entry['leader']['searchText'])) ?>"
									data-team4all-note-mode="leader"
									data-team4all-note-title="<?= p($entry['leader']['name']) ?>"
									data-team4all-note-uid="<?= p($entry['leader']['uid']) ?>"
									data-team4all-note-uri="<?= p($entry['leader']['uri']) ?>"
									data-team4all-note-address-book-id="<?= p((string)$entry['leader']['addressBookId']) ?>"
									data-team4all-note-content="<?= p(base64_encode($entry['leader']['note'])) ?>"
									data-team4all-leader-title="<?= p($entry['leader']['name']) ?>"
									data-team4all-leader-uid="<?= p($entry['leader']['uid']) ?>"
									data-team4all-leader-uri="<?= p($entry['leader']['uri']) ?>"
									data-team4all-leader-address-book-id="<?= p((string)$entry['leader']['addressBookId']) ?>"
									data-team4all-leader-content="<?= p(base64_encode($entry['leader']['note'])) ?>"
									data-team4all-detail-mode="leader"
									data-team4all-detail-title="<?= p($entry['leader']['name']) ?>"
									data-team4all-detail-company="<?= p($entry['leader']['companyDisplay'] ?? $entry['leader']['company']) ?>"
									data-team4all-detail-uid="<?= p($entry['leader']['uid']) ?>"
									data-team4all-detail-uri="<?= p($entry['leader']['uri']) ?>"
									data-team4all-detail-address-book-id="<?= p((string)$entry['leader']['addressBookId']) ?>"
									data-team4all-detail-prefix="<?= p(base64_encode($entry['leader']['prefix'])) ?>"
									data-team4all-detail-first-name="<?= p(base64_encode($entry['leader']['firstName'])) ?>"
									data-team4all-detail-last-name="<?= p(base64_encode($entry['leader']['lastName'])) ?>"
									data-team4all-detail-address-type="<?= p($entry['leader']['addressType']) ?>"
									data-team4all-detail-street-address="<?= p(base64_encode($entry['leader']['streetAddress'])) ?>"
									data-team4all-detail-postal-code="<?= p(base64_encode($entry['leader']['postalCode'])) ?>"
									data-team4all-detail-locality="<?= p(base64_encode($entry['leader']['locality'])) ?>"
									data-team4all-detail-addresses="<?= p(base64_encode(json_encode($entry['leader']['addresses'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]')) ?>"
									data-team4all-detail-telephones="<?= p(base64_encode($entry['leader']['telephones'])) ?>"
									data-team4all-detail-telephone-entries="<?= p(base64_encode(json_encode($entry['leader']['telephoneEntries'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]')) ?>"
									data-team4all-detail-emails="<?= p(base64_encode($entry['leader']['emails'])) ?>"
									data-team4all-detail-contact-groups="<?= p(base64_encode(json_encode($entry['leader']['contactGroups'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]')) ?>"
									data-team4all-leader-detail-title="<?= p($entry['leader']['name']) ?>"
									data-team4all-leader-detail-uid="<?= p($entry['leader']['uid']) ?>"
									data-team4all-leader-detail-uri="<?= p($entry['leader']['uri']) ?>"
									data-team4all-leader-detail-address-book-id="<?= p((string)$entry['leader']['addressBookId']) ?>"
									data-team4all-leader-detail-prefix="<?= p(base64_encode($entry['leader']['prefix'])) ?>"
									data-team4all-leader-detail-first-name="<?= p(base64_encode($entry['leader']['firstName'])) ?>"
									data-team4all-leader-detail-last-name="<?= p(base64_encode($entry['leader']['lastName'])) ?>"
									data-team4all-leader-detail-address-type="<?= p($entry['leader']['addressType']) ?>"
									data-team4all-leader-detail-street-address="<?= p(base64_encode($entry['leader']['streetAddress'])) ?>"
									data-team4all-leader-detail-postal-code="<?= p(base64_encode($entry['leader']['postalCode'])) ?>"
									data-team4all-leader-detail-locality="<?= p(base64_encode($entry['leader']['locality'])) ?>"
									data-team4all-leader-detail-addresses="<?= p(base64_encode(json_encode($entry['leader']['addresses'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]')) ?>"
									data-team4all-leader-detail-telephones="<?= p(base64_encode($entry['leader']['telephones'])) ?>"
									data-team4all-leader-detail-telephone-entries="<?= p(base64_encode(json_encode($entry['leader']['telephoneEntries'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]')) ?>"
									data-team4all-leader-detail-emails="<?= p(base64_encode($entry['leader']['emails'])) ?>"
									data-team4all-leader-detail-contact-groups="<?= p(base64_encode(json_encode($entry['leader']['contactGroups'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]')) ?>"
								>
									<strong><?= p($entry['company'] !== '' ? $entry['company'] : $entry['leader']['name']) ?></strong>
								</button>
							<?php else: ?>
								<div class="team4all-contact-group__header">
									<strong><?= p($entry['company']) ?></strong>
								</div>
							<?php endif; ?>
							<?php if ($entry['members'] !== []): ?>
								<ul class="team4all-contact-items<?= $entry['leader'] !== null ? ' team4all-contact-items--indented' : '' ?>">
									<?php foreach ($entry['members'] as $member): ?>
										<li>
											<span
											role="button"
											tabindex="0"
											class="team4all-contact-item team4all-contact-trigger team4all-contact-trigger--text"
											data-team4all-contact-search="<?= p(mb_strtolower($member['searchText'])) ?>"
											data-team4all-note-mode="<?= p($entry['leader'] !== null ? 'member' : 'single') ?>"
											data-team4all-note-title="<?= p($member['name']) ?>"
												data-team4all-note-uid="<?= p($member['uid']) ?>"
											data-team4all-note-uri="<?= p($member['uri']) ?>"
											data-team4all-note-address-book-id="<?= p((string)$member['addressBookId']) ?>"
											data-team4all-note-content="<?= p(base64_encode($member['note'])) ?>"
											data-team4all-detail-mode="<?= p($entry['leader'] !== null ? 'member' : 'single') ?>"
											data-team4all-detail-title="<?= p($member['name']) ?>"
											data-team4all-detail-company="<?= p($member['companyDisplay'] ?? $member['company']) ?>"
											data-team4all-detail-uid="<?= p($member['uid']) ?>"
											data-team4all-detail-uri="<?= p($member['uri']) ?>"
											data-team4all-detail-address-book-id="<?= p((string)$member['addressBookId']) ?>"
											data-team4all-detail-prefix="<?= p(base64_encode($member['prefix'])) ?>"
											data-team4all-detail-first-name="<?= p(base64_encode($member['firstName'])) ?>"
											data-team4all-detail-last-name="<?= p(base64_encode($member['lastName'])) ?>"
											data-team4all-detail-address-type="<?= p($member['addressType']) ?>"
											data-team4all-detail-street-address="<?= p(base64_encode($member['streetAddress'])) ?>"
											data-team4all-detail-postal-code="<?= p(base64_encode($member['postalCode'])) ?>"
											data-team4all-detail-locality="<?= p(base64_encode($member['locality'])) ?>"
											data-team4all-detail-addresses="<?= p(base64_encode(json_encode($member['addresses'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]')) ?>"
											data-team4all-detail-telephones="<?= p(base64_encode($member['telephones'])) ?>"
											data-team4all-detail-telephone-entries="<?= p(base64_encode(json_encode($member['telephoneEntries'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]')) ?>"
											data-team4all-detail-emails="<?= p(base64_encode($member['emails'])) ?>"
											data-team4all-detail-contact-groups="<?= p(base64_encode(json_encode($member['contactGroups'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]')) ?>"
											<?php if ($entry['leader'] !== null): ?>
												data-team4all-leader-title="<?= p($entry['leader']['name']) ?>"
												data-team4all-leader-uid="<?= p($entry['leader']['uid']) ?>"
												data-team4all-leader-uri="<?= p($entry['leader']['uri']) ?>"
												data-team4all-leader-address-book-id="<?= p((string)$entry['leader']['addressBookId']) ?>"
												data-team4all-leader-content="<?= p(base64_encode($entry['leader']['note'])) ?>"
												data-team4all-leader-detail-title="<?= p($entry['leader']['name']) ?>"
												data-team4all-leader-detail-uid="<?= p($entry['leader']['uid']) ?>"
												data-team4all-leader-detail-uri="<?= p($entry['leader']['uri']) ?>"
												data-team4all-leader-detail-address-book-id="<?= p((string)$entry['leader']['addressBookId']) ?>"
												data-team4all-leader-detail-prefix="<?= p(base64_encode($entry['leader']['prefix'])) ?>"
												data-team4all-leader-detail-first-name="<?= p(base64_encode($entry['leader']['firstName'])) ?>"
												data-team4all-leader-detail-last-name="<?= p(base64_encode($entry['leader']['lastName'])) ?>"
												data-team4all-leader-detail-address-type="<?= p($entry['leader']['addressType']) ?>"
												data-team4all-leader-detail-street-address="<?= p(base64_encode($entry['leader']['streetAddress'])) ?>"
												data-team4all-leader-detail-postal-code="<?= p(base64_encode($entry['leader']['postalCode'])) ?>"
												data-team4all-leader-detail-locality="<?= p(base64_encode($entry['leader']['locality'])) ?>"
												data-team4all-leader-detail-addresses="<?= p(base64_encode(json_encode($entry['leader']['addresses'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]')) ?>"
												data-team4all-leader-detail-telephones="<?= p(base64_encode($entry['leader']['telephones'])) ?>"
												data-team4all-leader-detail-telephone-entries="<?= p(base64_encode(json_encode($entry['leader']['telephoneEntries'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]')) ?>"
												data-team4all-leader-detail-emails="<?= p(base64_encode($entry['leader']['emails'])) ?>"
												data-team4all-leader-detail-contact-groups="<?= p(base64_encode(json_encode($entry['leader']['contactGroups'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]')) ?>"
											<?php endif; ?>
										>
											<strong><?= p($member['name']) ?></strong>
											</span>
										</li>
									<?php endforeach; ?>
								</ul>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
    </aside>

    <main class="team4all-main" aria-label="Arbeitsbereich" style="padding:0;min-width:0;height:100%;min-height:0;">
        <div
            class="team4all-main__panel"
            style="height:100%;min-height:0;width:100%;box-sizing:border-box;padding:10px 14px 14px;border:1px solid rgba(15,23,42,.14);border-radius:20px;background:#fff;box-shadow:0 12px 30px rgba(15,23,42,.10);"
        >
            <h2>Kontaktdaten</h2>
            <div id="team4all-details" class="team4all-details" data-mode="empty">
                <div id="team4all-details-empty" class="team4all-contact-placeholder">
                    <strong>Keine Kontaktdaten ausgewählt</strong>
                    <span>Bitte links einen Kontakt oder Gruppenleader anklicken.</span>
                </div>
                <div id="team4all-details-single" class="team4all-details__single" hidden>
                    <section
                        id="team4all-details-single-editor"
                        class="team4all-details__section"
                        data-team4all-contact-editor="true"
                    >
                        <h3 id="team4all-details-single-title" class="team4all-details__title"></h3>
                        <div class="team4all-details__grid">
                            <label class="team4all-details__field team4all-details__field--full">
                                <span>Anrede</span>
                                <input id="team4all-details-single-anrede" type="text" class="team4all-details__input" />
                            </label>
                            <label class="team4all-details__field team4all-details__field--full">
                                <span>Briefanrede</span>
                                <input id="team4all-details-single-briefanrede" type="text" class="team4all-details__input" />
                            </label>
                            <label class="team4all-details__field team4all-details__field--full">
                                <span>Titel</span>
                                <input id="team4all-details-single-prefix" type="text" class="team4all-details__input" />
                            </label>
                            <label class="team4all-details__field team4all-details__field--full">
                                <span>Vorname</span>
                                <input id="team4all-details-single-first-name" type="text" class="team4all-details__input" />
                            </label>
                            <label class="team4all-details__field team4all-details__field--full">
                                <span>Nachname</span>
                                <input id="team4all-details-single-last-name" type="text" class="team4all-details__input" />
                            </label>
                            <label class="team4all-details__field team4all-details__field--full">
                                <p id="team4all-details-single-address-origin" class="team4all-details__subtitle"></p>
                                <div id="team4all-details-single-addresses" class="team4all-details__addresses"></div>
                                <span>Straße &amp; Hausnummer</span>
                                <input id="team4all-details-single-street-address" type="text" class="team4all-details__input" spellcheck="true" />
                            </label>
                            <label class="team4all-details__field team4all-details__field--full">
                                <span>PLZ</span>
                                <div class="team4all-details__inline-row">
                                    <input id="team4all-details-single-postal-code" type="text" class="team4all-details__input team4all-details__input--postal" />
                                    <input id="team4all-details-single-locality" type="text" class="team4all-details__input" aria-label="Ort" />
                                </div>
                            </label>
                            <div class="team4all-details__field team4all-details__field--full">
                                <span>Telefonkontakte</span>
                                <div id="team4all-details-single-telephones" class="team4all-details__links"></div>
                            </div>
                            <div class="team4all-details__field team4all-details__field--full">
                                <span>Mailkontakte</span>
                                <div id="team4all-details-single-emails" class="team4all-details__links"></div>
                            </div>
                            <div class="team4all-details__field team4all-details__field--full">
                                <span>Kontaktgruppen</span>
                                <div id="team4all-details-single-contact-groups" class="team4all-details__links"></div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </main>

    <section
        class="team4all-main"
        aria-label="Arbeitsbereich Spalte 3"
        style="padding:0;min-width:0;height:100%;min-height:0;"
    >
        <div
            class="team4all-main__panel"
            style="height:100%;min-height:0;width:100%;box-sizing:border-box;padding:10px 14px 14px;border:1px solid rgba(15,23,42,.14);border-radius:20px;background:#fff;box-shadow:0 12px 30px rgba(15,23,42,.10);"
        >
            <h2>Reserviert</h2>
            <p>Hier kann später ein weiterer Funktionsbereich entstehen.</p>
        </div>
    </section>

    <section
        class="team4all-main"
        aria-label="Arbeitsbereich Spalte 4"
        style="padding:0;min-width:0;height:100%;min-height:0;"
    >
        <div
            class="team4all-main__panel"
            style="height:100%;min-height:0;width:100%;box-sizing:border-box;padding:10px 14px 14px;border:1px solid rgba(15,23,42,.14);border-radius:20px;background:#fff;box-shadow:0 12px 30px rgba(15,23,42,.10);"
        >
            <h2>Notizen</h2>
            <div id="team4all-notes" class="team4all-notes" data-mode="empty">
                <div id="team4all-notes-empty" class="team4all-contact-placeholder">
                    <strong>Keine Notiz ausgewählt</strong>
                    <span>Bitte links einen Kontakt oder Gruppenleader anklicken.</span>
                </div>
                <div id="team4all-notes-single" class="team4all-notes__single" hidden>
                    <h3 id="team4all-notes-single-title" class="team4all-notes__title"></h3>
                    <textarea id="team4all-notes-single-content" class="team4all-notes__content team4all-notes__editor" spellcheck="true"></textarea>
                </div>
                <div id="team4all-notes-split" class="team4all-notes__split" hidden>
                    <section class="team4all-notes__section">
                        <h3 id="team4all-notes-leader-title" class="team4all-notes__title"></h3>
                        <textarea id="team4all-notes-leader-content" class="team4all-notes__content team4all-notes__editor" spellcheck="true"></textarea>
                    </section>
                    <section id="team4all-notes-member-section" class="team4all-notes__section">
                        <h3 id="team4all-notes-member-title" class="team4all-notes__title"></h3>
                        <textarea id="team4all-notes-member-content" class="team4all-notes__content team4all-notes__editor" spellcheck="true"></textarea>
                    </section>
                </div>
            </div>
        </div>
    </section>
</div>
