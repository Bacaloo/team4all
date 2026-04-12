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
            <label class="team4all-search">
                <span class="team4all-search__label">Kontakte suchen</span>
                <input id="team4all-contact-search" type="search" placeholder="Name, Firma oder E-Mail" autocomplete="off" />
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
								<div
									class="team4all-contact-item team4all-contact-item--single"
									data-team4all-contact-search="<?= p(mb_strtolower($entry['person']['name'] . ' ' . $entry['person']['email'])) ?>"
								>
									<strong><?= p($entry['person']['name']) ?></strong>
									<?php if ($entry['person']['email'] !== ''): ?>
										<span><?= p($entry['person']['email']) ?></span>
									<?php else: ?>
										<span>Keine E-Mail-Adresse</span>
									<?php endif; ?>
								</div>
							</li>
							<?php continue; ?>
						<?php endif; ?>
						<li class="team4all-contact-group">
							<div class="team4all-contact-group__header">
								<strong><?= p($entry['company']) ?></strong>
								<span><?= count($entry['members']) + ($entry['leader'] !== null ? 1 : 0) ?></span>
							</div>
							<?php if ($entry['leader'] === null && count($entry['members']) === 1): ?>
								<div
									class="team4all-contact-item team4all-contact-item--single"
									data-team4all-contact-search="<?= p(mb_strtolower($entry['company'] . ' ' . $entry['members'][0]['name'] . ' ' . $entry['members'][0]['email'])) ?>"
								>
									<strong><?= p($entry['members'][0]['name']) ?></strong>
									<?php if ($entry['members'][0]['email'] !== ''): ?>
										<span><?= p($entry['members'][0]['email']) ?></span>
									<?php else: ?>
										<span>Keine E-Mail-Adresse</span>
									<?php endif; ?>
								</div>
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
										<li
											class="team4all-contact-item"
											data-team4all-contact-search="<?= p(mb_strtolower($entry['company'] . ' ' . $member['name'] . ' ' . $member['email'])) ?>"
										>
											<em>Member</em>
											<strong><?= p($member['name']) ?></strong>
											<?php if ($member['email'] !== ''): ?>
												<span><?= p($member['email']) ?></span>
											<?php else: ?>
												<span>Keine E-Mail-Adresse</span>
											<?php endif; ?>
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
