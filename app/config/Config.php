<?php
namespace Mico\Config;

class Config
{
    private static $config = null;

    public static function getConf()
    {
        if ( !isset(self::$config) ) {
            self::setupConf();
        }
        return self::$config;
    }

    private static function setupConf()
    {
        $config['migrationsDir'] = 'migrations';
        $dbConfig = parse_ini_file('database.ini');
        self::$config = array_merge($config, $dbConfig);
    }
}

// end of file
