<?php
namespace Mico\Cmd;

class Up extends \Mico\Command
{
    public function execute()
    {
        // migrationsディレクトリ中のファイル名の数値部分を走査して、最も大きな数値を最新バージョンとする
        $targetVer = $this->migrator->getLatestVersion();

        $this->migrator->run($targetVer);
    }
}

// end of file
