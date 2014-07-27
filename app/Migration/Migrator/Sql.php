<?php
namespace Mico\Migration\Migrator;

class Sql extends \Mico\Migration\Migrator
{
    const SECTION_HEADER_SIGN_UP = '-- *** mico Up START ***';

    const SECTION_FOOTER_SIGN_UP = '-- *** mico Up END ***';

    const SECTION_HEADER_SIGN_DOWN = '-- *** mico Down START ***';

    const SECTION_FOOTER_SIGN_DOWN = '-- *** mico Down END ***';

    /**
     * @param string $migrationFile
     * @param string $cmdName
     * @return bool  if answered yes:true  no:false
     */
    protected function confirmUpdateSchema($migrationFile, $cmdName)
    {
        $sql = $this->pickUpSqlStatement($migrationFile, $cmdName);
        echo "following SQL statement which is due to be executed. \n";
        echo $sql;
        echo "Are you sure you want to execute SQL? [y/N]\n";
        flush();
        ob_flush();
        $confirmation = trim(fgets(STDIN));
        if ($confirmation === 'y') {
            return true;
        }

        return false;
    }

    /**
     * @param string $migrationFile
     * @param string $cmdName
     */
    protected function updateSchema($migrationFile, $cmdName)
    {
        $sql = $this->pickUpSqlStatement($migrationFile, $cmdName);
        $this->db->exec($sql);
        echo "OK :  $migrationFile\n";
    }

    /**
     * return SQL statement for target command.
     * @param string $migrationFile
     * @param string $cmdName
     * @return string
     */
    private function pickUpSqlStatement($migrationFile, $cmdName)
    {
        $statement = '';
        $inTargetSection = false;
        //$contents = file($migrationFile, FILE_IGNORE_NEW_LINES);
        $contents = file($migrationFile);
        foreach ($contents as $line) {
            if ($this->matchHeaderSectionSign($cmdName, $line)) {
                if ($inTargetSection == true) {
                    throw new Exception('Detect invalid Section structure.');
                }
                $inTargetSection = true;
            }

            if ($inTargetSection) {
                $statement .= $line;
            }

            if ($this->matchFooterSectionSign($cmdName, $line)) {
                if ($inTargetSection == false) {
                    throw new Exception('Detect invalid Section structure.');
                }
                $inTargetSection = false;
            }
        }

        return $statement;
    }

    /**
     * @param string $cmdName
     * @param string $subject
     * @return bool header section sign matched or not.
     */
    private function matchHeaderSectionSign($cmdName, $subject)
    {
        return $this->matchSectionSign($cmdName, $subject, 'header');
    }

    /**
     * @param string $cmdName
     * @param string $subject
     * @return bool footer section sign matched or not.
     */
    private function matchFooterSectionSign($cmdName, $subject)
    {
        return $this->matchSectionSign($cmdName, $subject, 'footer');
    }

    /**
     * @param string $cmdName
     * @param string $subject
     * @param string $type
     * @return bool section sign matched or not.
     */
    private function matchSectionSign($cmdName, $subject, $type = 'header')
    {
        $cmdName = strtolower($cmdName);
        switch ($cmdName) {
        case 'up':
            $sectionHeaderSign = self::SECTION_HEADER_SIGN_UP;
            $sectionFooterSign = self::SECTION_FOOTER_SIGN_UP;
            break;
        case 'down':
            $sectionHeaderSign = self::SECTION_HEADER_SIGN_DOWN;
            $sectionFooterSign = self::SECTION_FOOTER_SIGN_DOWN;
            break;
        default:
            break;
        }

        $matched = false;
        $type = strtolower($type);
        switch ($type) {
        case 'header':
            if (preg_match('/^'.$sectionHeaderSign.'/', $line)) {
                $matched = true;
            }
            break;
        case 'footer':
            if (preg_match('/^'.$sectionFooterSign.'/', $line)) {
                $matched = true;
            }
            break;
        default:
            throw new Exception('Invalid section match type.');
        }

        return $matched;
    }

}

// end of file
