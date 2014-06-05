<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\Entities;

use Bitrix\Main\Type\DateTime;

class AppliedChangesLogModel extends BaseEntity {
    public
        $id, $groupLabel, $date, $success,
        $processName, $subjectName, $updateData,
        $originalData, $description, $setupLogId;

    private $_setupLog;

    public function __construct() {
        $this->date = new \DateTime();
    }

    static protected function modifyFromDb($data) {
        $result = array();
        foreach ($data as $name => $value) {
            if ($name == 'date') {
                if ($value instanceof DateTime) {
                    $value = $value->getValue();
                } else {
                    $value = new \DateTime($value);
                }
            }
            if (in_array($name, array('originalData', 'updateData'))) {
                $value = json_decode($value, true);
            }
            $result[$name] = $value;
        }
        return $result;
    }

    static protected function modifyToDb($data) {
        $result = array();
        foreach ($data as $name => $value) {
            if ($name == 'date' && $value instanceof \DateTime) {
                $value = DateTime::createFromPhp($value);
            }
            if (in_array($name, array('originalData', 'updateData'))) {
                $value = json_encode($value);
            }
            $result[$name] = $value;
        }
        return $result;
    }

    static protected function map() {
        return array(
            'id' => 'ID',
            'setupLogId' => 'SETUP_LOG_ID',
            'groupLabel' => 'GROUP_LABEL',
            'date' => 'DATE',
            'processName' => 'PROCESS',
            'subjectName' => 'SUBJECT',
            'updateData' => 'UPDATE_DATA',
            'originalData' => 'ORIGINAL_DATA',
            'success' => 'SUCCESS',
            'description' => 'DESCRIPTION'
        );
    }

    public function getSetupLog() {
        if (!$this->_setupLog) {
            $this->_setupLog = SetupLogModel::findOne(array(
                    'select' => array('=id' => $this->setupLogId)
                )
            );
        }
        return $this->_setupLog;
    }

    public function setSetupLog(SetupLogModel $model) {
        $this->_setupLog = $model;
        $model->id && $this->setupLogId = $model->id;
        return $this;
    }

    static protected function gatewayClass() {
        return AppliedChangesLogTable::className();
    }
}