<?php
require_once('vendor/autoload.php');

use \Mico\Command;

if ( !isset($argv[1]) ) {
    usage();
    exit;
}

$cmd = null;

switch ($argv[1]) {
case '-h':
case '--help':
    break;
case 'up':
    $cmd = new Command\Up();
    break;
case 'down':
    // $cmd = new \Command\Down();
    break;
case 'redo':
    // $cmd = new \Command\Redo();
    break;
case 'status':
    // $cmd = new \Command\Status();
    break;
case 'create':
    // $cmd = new \Command\Create();
    break;
case 'dbversion':
    // $cmd = new \Command\Dbversion();
    break;
default:
    echo "error: unknown command.\n";
    exit;
}
// $cmd->execute();


$conf = \Mico\Config\Config::getConf();
var_dump($conf);

try {
    $db = new PDO($conf['dsn'], $conf['user'], $conf['password']);
} catch (Exception $e) {
    echo 'Connection failed: ' .$e->getMessage();
    exit;
}

$sql = 'SELECT * FROM cards';
$stmt = $db->query($sql);
var_dump($stmt->fetchAll());

$db->beginTransaction();

//$sqlfile = '../sql/20140702103200_create_theme.sql';
//$sql = file_get_contents($sqlfile);


// @todo 実行予定SQLを画面に表示。実行してもいいか確認。
//       各SQLのバージョンも表示。
//       [current version] [next version] も表示。


try {
    $db->exec($sql);
    $db->commit();
} catch (Exception $e) {
    $db->rollBack();
    echo 'sql execute failed: ' . $e->getMessage();
}

echo 'sql execute completed.';




function usage()
{
    echo 'Usage: mico [command]'."\n";
}

// end of file
