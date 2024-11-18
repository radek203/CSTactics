<?php
declare(strict_types=1);

/** @var array $params */
/** @var array $config */
?>
<div class="container-fluid">
    <div class="container">
        <div class="row images-box">
            <h2 class="col-md-12 col-sm-12"><?php echo $params['tactic']->getName() ?></h2>
            <p>
                <?php
                $texts = $params['tactic']->getTexts();
                for ($id = 1; $id <= 5; $id++) { ?>
                    <span id="img-change-<?php echo $id ?>">
                        <?php
                        echo $id . '. ' . ($texts[$id - 1] ?: '---') . '<br>';
                        ?>
                    </span>
                <?php }
                ?>
            </p>
            <?php
            $imgs = $params['tactic']->getImgs();
            for ($id = 1; $id <= 5; $id++) {
                $url = $imgs[$id - 1] ? 'taktyki/' . $params['map']->getName() . '/' . $imgs[$id - 1] : 'hero.jpg';
                ?>
                <img style="display:<?php echo($id != 1 ? 'none' : 'block') ?>;" id="img-<?php echo $id ?>" src="<?php echo $config['site']['url'] . 'public/img/' . $url ?>" alt="">
                <?php
            }
            ?>
        </div>
    </div>
</div>