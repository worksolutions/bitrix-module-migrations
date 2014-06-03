<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\Entities;


use Bitrix\Main\Entity\DataManager;
use Bitrix\Main;

class AppliedChangesLogTable extends DataManager {
    public static function getFilePath() {
        // fuck )))
        return __FILE__;
    }

    public static function getTableName() {
        return 'ws_migrations_apply_changes_log';
    }

    public static function getMap() {
        return array(
            'ID' => array(),
            'GROUP_LABEL' => array(),
            'DATE' => array(),
            'PROCESS' => array(),
            'SUBJECT' => array(),
            'UPDATE_DATA' => array(),
            'ORIGINAL_DATA' => array(),
            'DESCRIPTION' => array()
        );
    }
}