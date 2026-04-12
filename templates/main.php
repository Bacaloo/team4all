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
        aria-label="Funktionsbereich"
        style="grid-column:1 / -1;display:flex;align-items:center;justify-content:space-between;gap:16px;height:50px;padding:0 20px;border:1px solid rgba(15,23,42,.14);border-radius:20px;background:#fff;box-shadow:0 12px 30px rgba(15,23,42,.10);box-sizing:border-box;"
    >
        <div class="team4all-toolbar__title">
            <p class="team4all-eyebrow" style="margin:0;">Funktionsbereich</p>
        </div>
        <div class="team4all-toolbar__content">
            <span>Hier entstehen die globalen Aktionen und Filter der App.</span>
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
        <p class="team4all-sidebar__copy">
            Team4All liest diese Kontakte direkt aus dem Standard-Adressbuch <strong>contacts</strong> und filtert auf die Kontaktgruppe <strong>Team4All</strong>.
        </p>
		<?php $team4AllContacts = $_['team4AllContacts'] ?? []; ?>
		<div class="team4all-contact-list">
			<div class="team4all-contact-list__header">
				<strong>Team4All-Kontakte</strong>
				<span><?= count($team4AllContacts) ?></span>
			</div>
			<?php if ($team4AllContacts === []): ?>
				<div class="team4all-contact-placeholder">
					<strong>Keine Kontakte gefunden</strong>
					<span>Im Adressbuch <code>contacts</code> ist aktuell kein sichtbarer Kontakt mit der Kontaktgruppe <code>Team4All</code> vorhanden.</span>
				</div>
			<?php else: ?>
				<ul class="team4all-contact-items">
					<?php foreach ($team4AllContacts as $contact): ?>
						<li class="team4all-contact-item">
							<strong><?= p($contact['name']) ?></strong>
							<?php if ($contact['email'] !== ''): ?>
								<span><?= p($contact['email']) ?></span>
							<?php else: ?>
								<span>Keine E-Mail-Adresse</span>
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
            <p class="team4all-eyebrow">Spalte 2</p>
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
            <p class="team4all-eyebrow">Spalte 3</p>
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
            <p class="team4all-eyebrow">Spalte 4</p>
            <h2>Reserviert</h2>
            <p>Diese Spalte bleibt für den nächsten Ausbauschritt frei.</p>
        </div>
    </section>
</div>
