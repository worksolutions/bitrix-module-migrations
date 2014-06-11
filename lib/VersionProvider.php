<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations;


use Bitrix\Main\DB\Exception;
use WS\Migrations\Entities\VersionHostAssociations;

class VersionProvider {

    /**
     * @var string
     */
    private $_version;

    public function __construct($version) {
        $this->_version = $version;
    }

    /**
     * @param $group
     * @param $referenceId
     * @return scalar
     */
    public function getIdentifier($group, $referenceId) {
        $dbRes = VersionHostAssociations::getList(array(
            'SELECT' => array('ORIGINAL_ID'),
            'filter' => array(
                '=VERSION' => $this->_version,
                '=GROUP' => $group,
                '=REFERENCE_ID' => $referenceId
            )
        ));
        $data = $dbRes->fetch();
        return $data['ORIGINAL_ID'] ?: $referenceId;
    }

    public function setAssociation($group, $referenceId, $originalId) {
        if ($referenceId != $this->getIdentifier($group, $referenceId)) {
            throw new Exception('Association exists');
        }
        $association = new VersionHostAssociations();
        return $association->add(array(
            'VERSION' => $this->_version,
            'GROUP' => $group,
            'REFERENCE_ID' => $referenceId,
            'ORIGINAL_ID' => $originalId
        ));
    }
}
