<?php

declare(strict_types=1);

script('team4all', 'main');
style('team4all', 'main');
?>
<div
    id="team4all-root"
    class="team4all-root"
    style="display:grid;grid-template-columns:20% repeat(3,minmax(0,1fr));grid-template-rows:50px minmax(0,1fr);gap:18px;padding:18px;min-height:calc(100vh - 50px);width:100%;max-width:none;box-sizing:border-box;align-items:stretch;align-content:stretch;"
>
    <section
        class="team4all-toolbar"
        aria-label="Kontaktsuche"
        style="grid-column:1 / -1;display:flex;align-items:center;justify-content:stretch;gap:16px;height:50px;padding:0 20px;border:1px solid rgba(15,23,42,.14);border-radius:20px;background:#fff;box-shadow:0 12px 30px rgba(15,23,42,.10);box-sizing:border-box;"
    >
        <div class="team4all-toolbar__content">
            <label class="team4all-search">
                <span class="team4all-search__label visually-hidden">Kontakte suchen</span>
                <input id="team4all-contact-search" type="search" placeholder="Kontakte suchen" autocomplete="off" />
            </label>
        </div>
    </section>

    <aside
        class="team4all-sidebar"
        aria-label="Kontaktebereich"
        style="display:flex;flex-direction:column;gap:20px;height:100%;min-height:0;padding:28px 18px;border:1px solid rgba(15,23,42,.14);border-radius:20px;background:#fff;box-shadow:0 12px 30px rgba(15,23,42,.10);box-sizing:border-box;"
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
								<button
									type="button"
									class="team4all-contact-item team4all-contact-item--single team4all-contact-trigger"
									data-team4all-contact-search="<?= p(mb_strtolower($entry['person']['searchText'])) ?>"
									data-team4all-note-mode="single"
									data-team4all-note-title="<?= p($entry['person']['name']) ?>"
									data-team4all-note-content="<?= p(base64_encode($entry['person']['note'])) ?>"
								>
									<strong><?= p($entry['person']['name']) ?></strong>
								</button>
							</li>
							<?php continue; ?>
						<?php endif; ?>
						<li class="team4all-contact-group">
							<?php if ($entry['leader'] !== null): ?>
								<button
									type="button"
									class="team4all-contact-group__header team4all-contact-trigger team4all-contact-trigger--header"
									data-team4all-note-mode="leader"
									data-team4all-leader-title="<?= p($entry['leader']['name']) ?>"
									data-team4all-leader-content="<?= p(base64_encode($entry['leader']['note'])) ?>"
								>
									<strong><?= p($entry['company']) ?></strong>
								</button>
							<?php else: ?>
								<div class="team4all-contact-group__header">
									<strong><?= p($entry['company']) ?></strong>
								</div>
							<?php endif; ?>
							<?php if ($entry['leader'] === null && count($entry['members']) === 1): ?>
								<button
									type="button"
									class="team4all-contact-item team4all-contact-item--single team4all-contact-trigger"
									data-team4all-contact-search="<?= p(mb_strtolower($entry['members'][0]['searchText'])) ?>"
									data-team4all-note-mode="single"
									data-team4all-note-title="<?= p($entry['members'][0]['name']) ?>"
									data-team4all-note-content="<?= p(base64_encode($entry['members'][0]['note'])) ?>"
								>
									<strong><?= p($entry['members'][0]['name']) ?></strong>
								</button>
							<?php else: ?>
								<?php if ($entry['leader'] === null): ?>
									<div class="team4all-contact-placeholder">
										<strong>Kein Gruppenleader</strong>
										<span>Es wurde kein Kontakt gefunden, bei dem Kontaktname und Firma uebereinstimmen.</span>
									</div>
								<?php endif; ?>
							<?php endif; ?>
							<?php if ($entry['members'] !== [] && !($entry['leader'] === null && count($entry['members']) === 1)): ?>
								<ul class="team4all-contact-items<?= $entry['leader'] !== null ? ' team4all-contact-items--indented' : '' ?>">
									<?php foreach ($entry['members'] as $member): ?>
										<li>
											<button
												type="button"
												class="team4all-contact-item team4all-contact-trigger"
											data-team4all-contact-search="<?= p(mb_strtolower($member['searchText'])) ?>"
												data-team4all-note-mode="<?= p($entry['leader'] !== null ? 'member' : 'single') ?>"
												data-team4all-note-title="<?= p($member['name']) ?>"
												data-team4all-note-content="<?= p(base64_encode($member['note'])) ?>"
												<?php if ($entry['leader'] !== null): ?>
													data-team4all-leader-title="<?= p($entry['leader']['name']) ?>"
													data-team4all-leader-content="<?= p(base64_encode($entry['leader']['note'])) ?>"
												<?php endif; ?>
											>
												<strong><?= p($member['name']) ?></strong>
											</button>
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
            style="height:100%;min-height:0;width:100%;box-sizing:border-box;padding:32px;border:1px solid rgba(15,23,42,.14);border-radius:20px;background:#fff;box-shadow:0 12px 30px rgba(15,23,42,.10);"
        >
            <h1>Arbeitsbereich</h1>
            <p>
                Diese Spalte ist als erster freier Arbeitsbereich vorbereitet.
            </p>
        </div>
    </main>

    <section
        class="team4all-main"
        aria-label="Arbeitsbereich Spalte 3"
        style="padding:0;min-width:0;height:100%;min-height:0;"
    >
        <div
            class="team4all-main__panel"
            style="height:100%;min-height:0;width:100%;box-sizing:border-box;padding:32px;border:1px solid rgba(15,23,42,.14);border-radius:20px;background:#fff;box-shadow:0 12px 30px rgba(15,23,42,.10);"
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
            style="height:100%;min-height:0;width:100%;box-sizing:border-box;padding:32px;border:1px solid rgba(15,23,42,.14);border-radius:20px;background:#fff;box-shadow:0 12px 30px rgba(15,23,42,.10);"
        >
            <h2>Notizen</h2>
            <div id="team4all-notes" class="team4all-notes" data-mode="empty">
                <div id="team4all-notes-empty" class="team4all-contact-placeholder">
                    <strong>Keine Notiz ausgewaehlt</strong>
                    <span>Bitte links einen Kontakt oder Gruppenleader anklicken.</span>
                </div>
                <div id="team4all-notes-single" class="team4all-notes__single" hidden>
                    <h3 id="team4all-notes-single-title" class="team4all-notes__title"></h3>
                    <div id="team4all-notes-single-content" class="team4all-notes__content"></div>
                </div>
                <div id="team4all-notes-split" class="team4all-notes__split" hidden>
                    <section class="team4all-notes__section">
                        <h3 id="team4all-notes-leader-title" class="team4all-notes__title"></h3>
                        <div id="team4all-notes-leader-content" class="team4all-notes__content"></div>
                    </section>
                    <section id="team4all-notes-member-section" class="team4all-notes__section">
                        <h3 id="team4all-notes-member-title" class="team4all-notes__title"></h3>
                        <div id="team4all-notes-member-content" class="team4all-notes__content"></div>
                    </section>
                </div>
            </div>
        </div>
    </section>
</div>
