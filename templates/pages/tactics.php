<?php
declare(strict_types=1);

/** @var array $params */
/** @var array $config */
?>
<div class="container-fluid">
	<div class="container">
		<div class="row">
			<h2 class="col-md-12 col-sm-12">Wybierz taktykę mapy <?php echo $params['map']->getName() ?>:</h2>
			<p>
				<?php
				if (count($params['tactics']) > 0) {
					foreach ($params['tactics'] as $tactic) { ?>
						<a href="<?php echo $config['site']['url'] . 'tactic/' . $tactic->getId() ?>"><?php echo $tactic->getName() ?></a>
						<br>
					<?php }
				} else {
					echo 'Brak taktyk :(';
				}
				?>
			</p>
		</div>
	</div>
</div>