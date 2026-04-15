<?php
/** @var array $_ */
?>
<div id="team4all-admin-settings" class="section">
	<h2><?php p($_['pageTitle'] ?? 'Team4All'); ?></h2>
	<p>
		<?php p('Waehle aus, welche freigegebenen Adressbuecher aus dem NC-Team Team4All in Team4All genutzt werden duerfen.'); ?>
	</p>
	<p>
		<?php p('Solange keine Auswahl gespeichert ist, bleiben alle geteilten Adressbuecher des Teams in Team4All nutzbar.'); ?>
	</p>

	<form method="post" action="<?php p($_['saveUrl'] ?? ''); ?>">
		<?php $selectedAddressBookIds = $_['selectedAddressBookIds'] ?? []; ?>
		<?php $addressBooks = $_['addressBooks'] ?? []; ?>

		<?php if ($addressBooks === []): ?>
			<p><?php p('Aktuell wurden keine geteilten Adressbuecher im NC-Team Team4All gefunden.'); ?></p>
		<?php else: ?>
			<?php foreach ($addressBooks as $addressBook): ?>
				<?php $inputId = 'team4all-address-book-' . md5((string)$addressBook['id']); ?>
				<p>
					<input
						id="<?php p($inputId); ?>"
						type="checkbox"
						class="checkbox"
						name="addressBookIds[]"
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

		<p>
			<button class="button button-primary" type="submit"><?php p('Auswahl speichern'); ?></button>
		</p>
	</form>
</div>
