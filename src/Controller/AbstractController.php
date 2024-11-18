<?php

declare(strict_types=1);

namespace CSApp\Controller;

use CSApp\Model\DatabaseConnection;
use CSApp\Model\SiteModel;
use CSApp\Request;

abstract class AbstractController
{
	protected Request $request;
	protected array $params;
	protected array $config;
	protected SiteModel $db;

	public function __construct(Request $request, array $config)
	{
		$this->request = $request;
		$this->config = $config;

		$this->db = new SiteModel(new DatabaseConnection($config['db']));
	}
}
