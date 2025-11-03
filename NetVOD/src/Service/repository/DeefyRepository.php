<?php

namespace Service\repository;


use PDO;

class DeefyRepository
{


    private \PDO $pdo;
    private static ?DeefyRepository $instance = null;
    private static array $config = [];

    private function __construct(array $conf)
    {
        $this->pdo = new \PDO($conf['dsn'], $conf['user'], $conf['pass'], [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
    }

    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new DeefyRepository(self::$config);
        }
        return self::$instance;
    }

    public static function setConfig(string $file)
    {
        $conf = parse_ini_file($file);
        if ($conf === false) {
            throw new \Exception("Error reading configuration file");
        }
        self::$config = ['dsn' => $conf['driver'] . ":host=" . $conf['host'] . ";dbname=" . $conf['database'], 'user' => $conf['username'], 'pass' => $conf['password']];
    }

    public function getPDO(): PDO {
        return $this->pdo;
    }




}

