<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\Entities;


use Bitrix\Main\Entity\DataManager;

class VersionHostAssociations extends DataManager {

    public static function getTableName() {
        return 'ws_migrations_version_host_associations';
    }

    public static function className() {
        return get_called_class();
    }

    public static function getFilePath() {
        // fuck )))
        return __FILE__;
    }

    public static function getMap() {
        return array(
            'ID' => array(
                'data_type' => 'integer',
                'primary' => true,
                'autocomplete' => true
            ),
            'VERSION' => array(
                'data_type' => 'string',
                'required' => true
            ),
            'GROUP' => array(
                'data_type' => 'string',
                'required' => true
            ),
            'REFERENCE_ID' => array(
                'data_type' => 'string',
                'required' => true
            ),
            'ORIGINAL_ID' => array(
                'data_type' => 'string',
                'required' => true
            )
        );
    }
} 