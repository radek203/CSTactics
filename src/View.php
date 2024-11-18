<?php

declare(strict_types=1);

namespace CSApp;

class View
{

    public function display(array $params, array $config): void
	{
		require_once('templates/layout.php');
	}

}