<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\Builder;

use WS\Migrations\Builder\Entity\Iblock;
use WS\Migrations\Builder\Entity\Property;

class IblockBuilder {

    /**
     * @var int
     */
    private $iblockId;

    /**
     * @var \CIBlockProperty
     */
    private $propertyGateway;
    /** @var  Iblock */
    private $iblock;
    /** @var  Property[] */
    private $properties;

    public function __construct() {
        \CModule::IncludeModule('iblock');
        $this->propertyGateway = new \CIBlockProperty();
    }

    /**
     * @param $code
     * @return Iblock
     * @throws \Exception
     */
    public function addIblock($code) {
        $this->iblock = new Iblock($code);
        return $this->iblock;
    }

    /**
     * @param $code
     * @return Iblock
     * @throws \Exception
     */
    public function updateIblock($code) {
        if (!$data = $this->findIblock($code)) {
            throw new BuilderException("Can't find iblock with code {$code}");
        }
        $this->iblock = new Iblock($code, $data);
        $this->iblockId = $this->iblock->id;
        return $this->iblock;
    }

    public function setIblockId($iblockId) {
        $this->iblockId = $iblockId;
        return $this;
    }

    /**
     * @param $name
     * @return Property
     * @throws \Exception
     */
    public function addProperty($name) {
        $prop = new Property($name);
        $this->properties[] = $prop;
        return $prop;
    }

    /**
     * @param $name
     * @return Property
     * @throws \Exception
     */
    public function updateProperty($name) {
        if (!$this->getIblockId()) {
            throw new BuilderException("Iblock not initialized");
        }
        if (!$data = $this->findProperty($name)) {
            throw new BuilderException("Can't find property with name {$name}");
        }
        $prop = new Property($name, $data);
        $this->properties[] = $prop;
        return $prop;
    }

    /**
     * @return int
     */
    public function getIblockId() {
        return $this->iblockId;
    }

    /**
     * @throws BuilderException
     */
    public function commit() {
        global $DB;
        $DB->StartTransaction();
        try {
            $this->commitIblock();

            if (!$this->getIblockId()) {
                throw new \Exception("Iblock did'nt set");
            }

            $this->commitProperties();
        } catch (\Exception $e) {
            $DB->Rollback();
            throw new BuilderException($e->getMessage());
        }
        $DB->Commit();
    }

    private function findIblock($code) {
        return \CIBlock::GetList(null, array(
            '=CODE' => $code
        ))->Fetch();
    }

    private function findProperty($name) {
        return \CIBlockProperty::GetList(null, array(
            '=NAME' => $name,
            'IBLOCK_ID' => $this->getIblockId()
        ))->Fetch();
    }

    private function commitIblock() {
        if (!$this->iblock) {
            return;
        }
        $ib = new \CIBlock();
        if ($this->iblock->id) {
            if (!$ib->Update($this->iblock->id, $this->iblock->getSaveData())) {
                throw new \Exception('Iblock was not updated. ' . $ib->LAST_ERROR);
            }
            $this->iblockId = $this->iblock->id;
        } else {
            $this->iblockId = $ib->Add($this->iblock->getSaveData());
        }
        if (!$this->iblockId) {
            throw new \Exception('Iblock was not created. ' . $ib->LAST_ERROR);
        }
    }

    private function commitProperties() {
        foreach ($this->properties as $property) {
            if ($property->id > 0) {
                continue;
            }
            $id = $this->propertyGateway->Add(array_merge($property->getSaveData(), array('IBLOCK_ID' => $this->getIblockId())));
            if (!$id) {
                throw new \Exception("Property was {$property->name} not created. " . $this->propertyGateway->LAST_ERROR);
            }
        }

        foreach ($this->properties as $property) {
            if (!$property->id) {
                continue;
            }
            $id = $this->propertyGateway->Update($property->id, array_merge($property->getSaveData(), array('IBLOCK_ID' => $this->getIblockId())));
            if (!$id) {
                throw new \Exception("Property was {$property->name} not updated. " . $this->propertyGateway->LAST_ERROR);
            }
        }
    }
}
