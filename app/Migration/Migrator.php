<?php
namespace Mico\Migration;

abstract class Migrator
{
    protected $migrationsDir = null;

    protected $db = null;

    public function __construct()
    {
        $conf = \Mico\Config\Config::getConf();
        $this->db = new \PDO($conf['dsn'], $conf['user'], $conf['password']);
        $this->migrationsDir = $conf['migrationsDir'];
    }

    abstract protected function updateSchema($migrationFile, $cmdName);

    /**
     * run migrations.
     * @param int $targetVer migrate target version
     * @return
     */
    public function run($targetVer)
    {
        // DBを参照して、currentに現在バージョン番号を代入
        // 現在バージョンがなければ0、バージョン用テーブルがなければ作る
        $currentVer = $this->ensureSchemaVersion();

        // newMigration()で作成された、実行対象バージョンのMigration構造体を集める
        $migrations = $this->collectMigrations($currentVer, $targetVer);

        if (count($migrations) == 0) {
            echo 'no migrations to run.';
            return;
        }

        if ($currentVer < $targetVer) {
            sort($migrations);
            $cmdName = 'up';
        } else {
            rsort($migrations);
            $cmdName = 'down';
        }

        if (!$this->confirmUpdateSchema($migrations)) {
            echo "migration stopped.\n";
            return;
        }

        echo "migration start.\n";
        foreach ($migrations as $m) {
            try {
                $this->updateSchema($m, $cmdName);
            } catch (Exception $e) {
                echo "apply migration failed. migration=[{$m}]\n";
                throw $e;
            }
            echo "apply migration succeed. migration=[{$m}]\n";
        }
    }

    /**
     * @param string $migrationFile
     * @param string $cmdName
     * @return bool  if answered yes:true  no:false
     */
    protected function confirmUpdateSchema($migrationFiles)
    {
        echo "following files which is due to be executed. \n";
        foreach ($migrationFiles as $m) {
            echo $m."\n";
        }
        echo "Are you sure you want to perform migrate? [y/N]\n";
        flush();
        ob_flush();
        $confirmation = trim(fgets(STDIN));
        if ($confirmation === 'y') {
            return true;
        }

        return false;
    }



    /**
     * collect migration script filenames
     * @param int $currentVer current schema version
     * @param int $targetVer migrate target version
     * @return array
     */
    public function collectMigrations($currentVer, $targetVer)
    {
        $migrationFiles = scandir($this->migrationsDir);
        $targetMigrations = array();
        foreach ($migrationFiles as $file) {
            preg_match('/^([0-9]+)_.*$/', $file, $matches);
            if (isset($matches[1])) {
                $version = $matches[1];
                if ($version <= $currentVer) {
                    continue;
                }
                if ($version <= $targetVer) {
                    $targetMigrations[] = $file;
                }
            }
        }

        return $targetMigrations;
    }

    /**
     * ensure schema version exists in DB.
     * create schema version table if is does not exist.
     * @return int current schema version.
     */
    public function ensureSchemaVersion()
    {
        $sql = "SHOW TABLES LIKE 'mico_schema_version'";
        $rows = $this->db->query($sql)->fetchAll();
        if (count($rows) == 0) {
            $this->createSchemaVersionTable();
        }
        $currentVer = $this->getCurrentSchemaVersion();

        return $currentVer;
    }

    /**
     * get current version from DB.
     * @return int current schema version.
     */
    public function getCurrentSchemaVersion()
    {
        $currentVer = 0;
        $sql = 'SELECT * FROM mico_schema_version WHERE is_applied = true ORDER BY version DESC LIMIT 1';
        $rows = $this->db->query($sql)->fetchAll();
        if (count($rows) > 0) {
            $currentVer = $rows[0]['version'];
        }

        return $currentVer;
    }

    /**
     * get previous version from DB.
     * @return int previous schema version.
     */
    public function getPreviousSchemaVersion()
    {
        throw new Exception('not implemented.');
    }


    /**
     * get latest possible version.
     * @return int
     */
    public function getLatestVersion()
    {
        $migrationFiles = scandir($this->migrationsDir);
        $versions = array();
        foreach ($migrationFiles as $file) {
            preg_match('/^([0-9]+)_.*$/', $file, $matches);
            if (isset($matches[1])) {
                $versions[] = $matches[1];
            }
        }

        $version = -1;
        if (count($versions) == 0) {
            return $version;
        }

        $ret = rsort($versions);
        if ($ret = false) {
            throw new Exception('migrations sort failed.');
        }
        $version = $versions[0];

        return $version;
    }


    /**
     * create migrasion file.
     * @param $name migration name
     * @param $directory directory of deploy migration file
     * @return void
     */
    public function createMigration($name, $directory)
    {
        throw new Exception('not implemented.');

        $nowDatetime = date("YmdHis", time());
        $extension = 'sql';
        $filename = sprintf('%s_%s.%s', $nowDatetime, $name, $extension);

        // @todo ファイルをディレクトリに保存する
        $fullFilename = $directory . '/' . $filename;
        $contents = $this->getMigrationSqlTemplate();
        file_put_contents($fullFilename, $contents);
    }

    private function createSchemaVersionTable()
    {
        $sql = 'CREATE TABLE mico_schema_version ('
            .      ' id int NOT NULL AUTO_INCREMENT,'
            .      ' version bigint NOT NULL,'
            .      ' description varchar(2000),'
            .      ' script varchar(256),'
            .      ' is_applied boolean NOT NULL,'
            .      ' updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,'
            .      ' PRIMARY KEY(id)'
            .  ')';
        $db->exec($sql);
    }

    private function getMigrationSqlTemplate()
    {
        $sqlMigrationTemplate = ''
            . '--'."\n"
            . '-- *** mico Up START ***'."\n"
            . '-- this section(Until it becomes a line with a [mico Up END] sign)'
            . '-- is performed by the Up command.'
            . '--'."\n"
            . "\n"
            . "\n"
            . "\n"
            . '-- *** mico Up END ***'."\n"
            . "\n"
            . "\n"
            . "\n"
            . '--'."\n"
            . '-- *** mico Down START ***'."\n"
            . '-- this section(Until it becomes a line with a [mico Down END] sign)'
            . '-- is performed by the Up command.'
            . '--'."\n"
            . "\n"
            . "\n"
            . "\n"
            . '-- *** mico Down END ***'."\n"
            ;

        return $sqlMigrationTemplate;
    }
}

// end of file
