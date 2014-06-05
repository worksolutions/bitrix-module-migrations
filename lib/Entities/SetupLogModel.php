<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\Entities;


use Bitrix\Main\Type\DateTime;

class SetupLogModel extends BaseEntity {
    public $id, $date, $user;

    public function __construct() {
        $this->date = new \DateTime();
    }

    static protected function map() {
        return array(
            'id' => 'ID',
            'date' => 'DATE',
            'user' => 'USER'
        );
    }

    static protected function gatewayClass() {
        return SetupLogTable::className();
    }

    static protected function modifyFromDb($data) {
        if ($data['date'] instanceof DateTime) {
            $data['date'] = $data['date']->getValue();
        } else {
            $data['date']= new \DateTime($data['date']);
        }
        return $data;
    }

    static protected function modifyToDb($data) {
        $data['date'] && $data['date'] instanceof \DateTime && $data['date'] = DateTime::createFromPhp($data['date']);
        return $data;
    }
}