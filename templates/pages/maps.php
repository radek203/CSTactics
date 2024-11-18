<?php
declare(strict_types=1);

/** @var array $params */
/** @var array $config */
?>
<div class="container-fluid">
	<div class="container">
		<div class="row">
			<a href="<?php echo $config['site']['url'] ?>?logout=1"></a>
			<h2 class="col-md-12 col-sm-12">Wybierz mapę:</h2>
			<p class="col-md-12 col-sm-12">
				<?php
				if (count($params['maps']) > 0) {
					foreach ($params['maps'] as $map) { ?>
						<a href="<?php echo $config['site']['url'] . 'tactics/' . $map->getId() ?>"><?php echo $map->getName() ?></a><br>
					<?php }
				} else {
					echo 'Brak map :(';
				}
				?>
			</p>
			<p class="col-md-12 col-sm-12">
                <?php
                if (isset($params['search_error'])) {
                    echo $params['search_error'];
                }
                ?>
			</p>
		</div>
	</div>
</div>