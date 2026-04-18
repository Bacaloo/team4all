<?php
/** @var array $_ */
?>
<div id="team4all-admin-settings" class="section">
	<style>
		.team4all-admin-pill-grid {
			display: flex;
			flex-wrap: wrap;
			gap: 8px;
			margin-top: 8px;
		}

		.team4all-admin-pill-grid input[type="checkbox"] {
			position: absolute;
			opacity: 0;
			pointer-events: none;
		}

		.team4all-admin-pill {
			display: inline-flex;
			align-items: center;
			gap: 6px;
			padding: 6px 12px;
			border: 1px solid rgba(15,23,42,.18);
			border-radius: 999px;
			background: #f8fbff;
			cursor: pointer;
			user-select: none;
		}

		.team4all-admin-pill input[type="checkbox"]:checked + span {
			font-weight: 700;
		}

		.team4all-admin-pill:has(input[type="checkbox"]:checked) {
			border-color: #3d6a8b;
			background: rgba(61,106,139,.12);
		}
	</style>
	<h2><?php p($_['pageTitle'] ?? 'Team4All'); ?></h2>
	<p>
		<?php p('Waehle fuer jeden Nutzer einzeln aus, welche fuer ihn sichtbaren freigegebenen Adressbuecher aus dem NC-Team Team4All in Team4All genutzt werden duerfen.'); ?>
	</p>
	<p>
		<?php p('Solange fuer einen Nutzer keine Auswahl gespeichert ist, bleiben fuer diesen Nutzer alle geteilten Adressbuecher des Teams in Team4All nutzbar.'); ?>
	</p>

	<form method="post" action="<?php p($_['saveUrl'] ?? ''); ?>">
		<?php $selectedAddressBookIdsByUser = $_['selectedAddressBookIdsByUser'] ?? []; ?>
		<?php $defaultAddressBookIdsByUser = $_['defaultAddressBookIdsByUser'] ?? []; ?>
		<?php $addressBooksByUser = $_['addressBooksByUser'] ?? []; ?>
		<?php $availableContactGroups = $_['availableContactGroups'] ?? []; ?>
		<?php $selectedFrontendFilterGroups = $_['selectedFrontendFilterGroups'] ?? []; ?>

		<?php if ($addressBooksByUser === []): ?>
			<p><?php p('Aktuell wurden keine geteilten Adressbuecher im NC-Team Team4All gefunden.'); ?></p>
		<?php else: ?>
			<?php foreach ($addressBooksByUser as $userEntry): ?>
				<?php
				$userUid = (string)($userEntry['uid'] ?? '');
				$displayName = (string)($userEntry['displayName'] ?? $userUid);
				$addressBooks = $userEntry['addressBooks'] ?? [];
				$selectedAddressBookIds = $selectedAddressBookIdsByUser[$userUid] ?? [];
				$defaultAddressBookId = $defaultAddressBookIdsByUser[$userUid] ?? '';
				?>
				<input type="hidden" name="teamUserUids[]" value="<?php p($userUid); ?>">
				<fieldset style="margin:0 0 24px;padding:16px;border:1px solid rgba(15,23,42,.14);border-radius:12px;">
					<legend><strong><?php p($displayName); ?></strong><?php if ($displayName !== $userUid): ?> <span>(<?php p($userUid); ?>)</span><?php endif; ?></legend>

					<?php if ($addressBooks === []): ?>
						<p><?php p('Fuer diesen Nutzer wurden aktuell keine freigegebenen Team4All-Adressbuecher gefunden.'); ?></p>
					<?php else: ?>
						<?php foreach ($addressBooks as $addressBook): ?>
							<?php $inputId = 'team4all-address-book-' . md5($userUid . '|' . (string)$addressBook['id']); ?>
							<p>
								<input
									id="<?php p($inputId); ?>"
									type="checkbox"
									class="checkbox"
									name="addressBookIds[<?php p($userUid); ?>][]"
									value="<?php p($addressBook['id']); ?>"
									<?php if (in_array($addressBook['id'], $selectedAddressBookIds, true)): ?>checked="checked"<?php endif; ?>
								/>
								<label for="<?php p($inputId); ?>">
									<strong><?php p($addressBook['label']); ?></strong>
									<br>
									<span><?php p('Sichtbar fuer: ' . implode(', ', $addressBook['visibleFor'] ?? [])); ?></span>
								</label>
							</p>
						<?php endforeach; ?>
					<?php endif; ?>

					<h3><?php p('Standardadressbuch fuer automatisch angelegte Datensaetze'); ?></h3>
					<p><?php p('Hier wird festgelegt, in welchem der fuer diesen Nutzer ausgewaehlten Adressbuecher automatisch erzeugte Team4All-Kontakte angelegt werden.'); ?></p>
					<p>
						<label for="<?php p('team4all-default-address-book-' . md5($userUid)); ?>"><?php p('Standardadressbuch'); ?></label>
						<br>
						<select id="<?php p('team4all-default-address-book-' . md5($userUid)); ?>" name="defaultAddressBookId[<?php p($userUid); ?>]">
							<option value=""><?php p('Kein festes Standardadressbuch'); ?></option>
							<?php foreach ($addressBooks as $addressBook): ?>
								<option
									value="<?php p($addressBook['id']); ?>"
									<?php if ((string)$addressBook['id'] === (string)$defaultAddressBookId): ?>selected="selected"<?php endif; ?>
								>
									<?php p($addressBook['label']); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</p>
				</fieldset>
			<?php endforeach; ?>
		<?php endif; ?>

		<h3><?php p('Klickbare Frontend-Filter'); ?></h3>
		<p><?php p('Waehle die Kontaktgruppen aus, die im Frontend als klickbare Filter angeboten werden sollen.'); ?></p>
		<?php if ($availableContactGroups === []): ?>
			<p><?php p('Aktuell wurden keine Kontaktgruppen fuer die Filterauswahl gefunden.'); ?></p>
		<?php else: ?>
			<div class="team4all-admin-pill-grid">
				<?php foreach ($availableContactGroups as $contactGroup): ?>
					<?php $inputId = 'team4all-filter-group-' . md5((string)$contactGroup); ?>
					<label class="team4all-admin-pill" for="<?php p($inputId); ?>">
						<input
							id="<?php p($inputId); ?>"
							type="checkbox"
							name="frontendFilterGroups[]"
							value="<?php p($contactGroup); ?>"
							<?php if (in_array($contactGroup, $selectedFrontendFilterGroups, true)): ?>checked="checked"<?php endif; ?>
						/>
						<span><?php p($contactGroup); ?></span>
					</label>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<p>
			<button class="button button-primary" type="submit"><?php p('Auswahl speichern'); ?></button>
		</p>
	</form>
	<hr style="margin:32px 0 10px;border:0;border-top:1px solid rgba(15,23,42,.14);">
	<p style="margin:0;font-size:12px;color:var(--color-text-maxcontrast);">
		<?php p('Team4All Version ' . ($_['appVersion'] ?? 'unbekannt')); ?>
	</p>
</div>
