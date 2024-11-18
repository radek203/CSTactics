<?php

declare(strict_types=1);

namespace CSCasesApp;

/** @var array $params */
/** @var array $config */
?>

<head>
    <meta charset="utf-8">
    <link rel="manifest" href="<?php echo $config['site']['url']; ?>manifest.webmanifest">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?php echo $config['site']['name_title']; ?></title>
    <link href="<?php echo $config['site']['url']; ?>public/css/main.css" rel="stylesheet">
    <link rel="shortcut icon" type="image/png" href="<?php echo $config['site']['url']; ?>public/img/favicon.ico">
    <link rel="apple-touch-icon" href="<?php echo $config['site']['url']; ?>public/img/favicon.ico">
    <meta property="og:locale" content="pl_PL">
    <meta property="og:site_name" content="<?php echo $config['site']['name']; ?>">
    <meta name="description" content="<?php echo $config['site']['name']; ?>">
    <meta name="theme-color" content="<?php echo $config['site']['color']['base']; ?>">
</head>