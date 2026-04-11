<?php

declare(strict_types=1);

script('team4all', 'main');
style('team4all', 'main');
?>
<div id="team4all-root" class="team4all-root">
    <aside class="team4all-sidebar" aria-label="Kontaktebereich">
        <div class="team4all-sidebar__header">
            <img class="team4all-logo" src="<?= image_path('team4all', 'app.svg') ?>" alt="Team4All Logo" />
            <div>
                <p class="team4all-eyebrow">Team4All</p>
                <h2>Kontakte</h2>
            </div>
        </div>
        <p class="team4all-sidebar__copy">
            Dieser Bereich ist für Daten aus der Contacts-App vorgesehen.
        </p>
        <div class="team4all-contact-placeholder">
            <strong>Platzhalter</strong>
            <span>Später folgen hier Kontaktlisten, Rollen oder Ansprechpersonen.</span>
        </div>
    </aside>

    <main class="team4all-main" aria-label="Arbeitsbereich">
        <div class="team4all-main__panel">
            <p class="team4all-eyebrow">Arbeitsbereich</p>
            <h1>Team4All</h1>
            <p>
                Der erste Bildschirmbereich ist jetzt vorbereitet. Links steht die schmale Kontaktspalte bereit,
                rechts bleibt Platz für die nächsten Funktionsbausteine.
            </p>
        </div>
    </main>
</div>
