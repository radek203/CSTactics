<?php
declare(strict_types=1);

/** @var array $params */
/** @var array $config */
?>
<div class="container-fluid">
    <div class="container">
        <div class="row">
            <h2 class="col-md-12 col-sm-12">Zaloguj się</h2>
            <?php
            if (!$params['logged_in']) {
                ?>
                <a href="<?php echo $config['site']['url'] ?>?login=1">LOGIN</a>
                <p class="col-md-12 col-sm-12">Musisz się zalogować!</p>
                <?php
            }
            if (isset($params['login_error'])) {
                echo $params['login_error'];
            }
            ?>
        </div>
    </div>
</div>