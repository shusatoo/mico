<?php
namespace Mico;

abstract class Command
{
    protected $config = null;

    protected $db = null;

    protected $migrator = null;

    public function __construct()
    {
        $this->config = \Mico\Config\Config::getConf();
        $this->db = new \PDO($this->config['dsn'], $this->config['user'], $this->config['password']);
        $this->migrator = new \Mico\Migration\Migrator\Sql();
    }

    abstract public function execute();
}

// end of file
