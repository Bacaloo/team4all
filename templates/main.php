<?php

declare(strict_types=1);

script('team4all', 'main');
style('team4all', 'main');
?>
<div
    id="team4all-root"
    class="team4all-root"
    style="display:grid;grid-template-columns:minmax(260px,1.3fr) minmax(180px,1fr) minmax(180px,1fr) minmax(180px,1fr);gap:18px;padding:18px;min-height:calc(100vh - 50px);"
>
    <aside
        class="team4all-sidebar"
        aria-label="Kontaktebereich"
        style="display:flex;flex-direction:column;gap:20px;padding:28px 18px;border:1px solid rgba(15,23,42,.14);border-radius:20px;background:#fff;box-shadow:0 12px 30px rgba(15,23,42,.10);"
    >
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

    <main class="team4all-main" aria-label="Arbeitsbereich" style="padding:0;">
        <div
            class="team4all-main__panel"
            style="min-height:100%;padding:32px;border:1px solid rgba(15,23,42,.14);border-radius:20px;background:#fff;box-shadow:0 12px 30px rgba(15,23,42,.10);"
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
        style="padding:0;"
    >
        <div
            class="team4all-main__panel"
            style="min-height:100%;padding:32px;border:1px solid rgba(15,23,42,.14);border-radius:20px;background:#fff;box-shadow:0 12px 30px rgba(15,23,42,.10);"
        >
            <p class="team4all-eyebrow">Spalte 3</p>
            <h2>Reserviert</h2>
            <p>Hier kann später ein weiterer Funktionsbereich entstehen.</p>
        </div>
    </section>

    <section
        class="team4all-main"
        aria-label="Arbeitsbereich Spalte 4"
        style="padding:0;"
    >
        <div
            class="team4all-main__panel"
            style="min-height:100%;padding:32px;border:1px solid rgba(15,23,42,.14);border-radius:20px;background:#fff;box-shadow:0 12px 30px rgba(15,23,42,.10);"
        >
            <p class="team4all-eyebrow">Spalte 4</p>
            <h2>Reserviert</h2>
            <p>Diese Spalte bleibt für den nächsten Ausbauschritt frei.</p>
        </div>
    </section>
</div>
