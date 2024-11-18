<!DOCTYPE html>
<html lang="pl">
<?php
require_once('templates/header.php');
?>
<body>
<?php
/** @var array $params */

require_once('templates/pages/' . $params['site'] . '.php');

require_once('templates/footer.php');
?>
</body>
</html>