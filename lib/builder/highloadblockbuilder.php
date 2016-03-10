<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace Domain\Migrations;

use Bitrix\Highloadblock\HighloadBlockTable;

class HighLoadBlockBuilder {

    private $fieldsGateway;

    /**
     * @var int
     */
    private $iblockId;

    public function __construct() {
        \CModule::IncludeModule('iblock');
        \CModule::IncludeModule('highloadblock');
        $this->fieldsGateway = new \CUserTypeEntity();
    }

    /**
     * @param $name
     * @param $tableName
     * @return $this
     * @throws \Bitrix\Main\SystemException
     * @throws \Exception
     */
    public function createTable($name, $tableName) {
        $hbRes = HighloadBlockTable::add(array(
            'NAME'       => $name,
            'TABLE_NAME' => $tableName
        ));
        if (!$hbRes->isSuccess()){
            throw new \Exception('Cant create block by table name `'.$tableName.'` '.implode(', ', $hbRes->getErrorMessages()));
        }
        $this->iblockId = $hbRes->getId();
        return $this;
    }

    /**
     * @param $tableName
     * @return $this
     * @throws \Bitrix\Main\SystemException
     * @throws \Exception
     */
    public function findTable($tableName) {
        $hbRes = HighloadBlockTable::getList(array(
            'filter' => array(
                'TABLE_NAME' => $tableName
            )
        ));
        if (!($table = $hbRes->fetch())){
            throw new \Exception('Cant find block by table name `$tableName` '.implode(', ', $hbRes->getErrorMessages()));
        }
        $this->iblockId = $table['ID'];
        return $this;
    }

    /**
     * @param $name
     * @param $type
     * @param $label
     * @return $this
     * @throws \Exception
     */
    public function createSimpleField($name, $type, $label) {
        if (!$this->getIblockId()) {
            throw new \Exception('Set iblock id before');
        }
        $default = array(
            "XML_ID"         => "",
            "SORT"           => "",
            "MULTIPLE"       => "N",
            "MANDATORY"      => "N",
            "SHOW_FILTER"    => "Y",
            "SHOW_IN_LIST"   => "Y",
            "EDIT_IN_LIST"   => "Y",
            "IS_SEARCHABLE"  => "N",
            "SETTINGS"        => "",
            "ERROR_MESSAGE"     => "",
            "HELP_MESSAGE"      => "",
        );
        $res = $this->fieldsGateway->Add(array_merge($default, array(
            "ENTITY_ID"      => "HLBLOCK_" . $this->getIblockId(),
            "FIELD_NAME"     => $name,
            "USER_TYPE_ID"   => $type,
            "EDIT_FORM_LABEL" => array(
                "ru" => $label
            ),
            "LIST_COLUMN_LABEL" => array(
                "ru" => $label
            ),
            "LIST_FILTER_LABEL" => array(
                "ru" => $label
            ),
        )));
        return $this;
    }

    /**
     * @return int
     */
    public function getIblockId() {
        return $this->iblockId;
    }

    /**
     * @param $value
     * @throws \Exception
     */
    public function setIblockId($value) {
        if ($this->iblockId) {
            throw new \Exception('Iblock id was exist');
        }
        $this->iblockId = $value;
    }

    public function removeSimpleField($name) {
        if (!$this->getIblockId()) {
            throw new \Exception('Set iblock id before');
        }
        $field = $this->fieldsGateway->GetList(array(), array(
            'FIELD_NAME' => $name,
            "ENTITY_ID"      => "HLBLOCK_" . $this->getIblockId(),
        ))->Fetch();
        $this->fieldsGateway->Delete($field['ID']);

        return $this;
    }
}
