<?php

declare(strict_types=1);

namespace CSApp\Model;

use PDO;
use PDOException;

class DatabaseConnection
{

    protected array $config;
    protected PDO $db;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->makeConnection($this->config);
    }

    public function makeConnection(array $config): void
    {
        try {
            $this->db = new PDO('mysql:host=' . $config['host'] . ';dbname=' . $config['database'] . ';port=' . $config['port'], $config['username'], $config['password'], [PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8']);
        } catch (PDOException) {
            exit('A connection to the site\'s database could not be established! Refresh the page, and if the error persists, contact the Administrator!');
        }
    }

    public function getDatabase(): PDO
    {
        return $this->db;
    }

}