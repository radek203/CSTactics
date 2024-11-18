<?php

declare(strict_types=1);

namespace CSApp\Model;

abstract class AbstractModel
{
	protected DatabaseConnection $db;

	public function __construct(DatabaseConnection $db)
	{
		$this->db = $db;
	}
}