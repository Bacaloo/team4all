<?php
/** @var array $_ */
/** @var array{missingApps?: list<array{id: string, name: string}>} $params */

script('team4all', 'main');
style('team4all', 'main');

$missingApps = $params['missingApps'] ?? [];
?>
<div id="team4all-root" class="team4all-root team4all-status">
	<img class="team4all-logo" src="<?= image_path('team4all', 'app.svg') ?>" alt="Team4All Logo" />
	<h2>Team4All</h2>
	<p>Die App kann erst genutzt werden, wenn alle Pflicht-Apps in Nextcloud aktiviert sind.</p>
	<?php if ($missingApps !== []): ?>
		<p>Aktuell fehlen:</p>
		<ul class="team4all-missing-list">
			<?php foreach ($missingApps as $missingApp): ?>
				<li>
					<strong><?= p($missingApp['name']) ?></strong>
					<span class="team4all-app-id">(<?= p($missingApp['id']) ?>)</span>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</div>
