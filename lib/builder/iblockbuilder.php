<?php

namespace WS\Migrations\Builder;

use WS\Migrations\Builder\Entity\Iblock;
use WS\Migrations\Builder\Entity\IblockType;
use WS\Migrations\Builder\Entity\Property;

class IblockBuilder {
    /** @var  Iblock */
    private $iblock;
    /** @var  Property[] */
    private $properties;
    /** @var  IblockType */
    private $iblockType;

    public function __construct() {
        \CModule::IncludeModule('iblock');
    }

    public function reset() {
        $this->iblock = null;
        $this->iblockType = null;
        $this->properties = null;
    }

    public function addIblockType($type) {
        if ($this->iblockType) {
            throw new BuilderException('IblockType already set');
        }
        $this->iblockType = new IblockType($type);
        return $this->iblockType;
    }

    public function updateIblockType($type) {
        if ($this->iblockType) {
            throw new BuilderException('IblockType already set');
        }
        if (!$data = $this->findIblockType($type)) {
            throw new BuilderException("Can't find iblockType with type {$type}");
        }
        $this->iblockType = new IblockType($type, $data);
        return $this->iblockType;
    }

    /**
     * @param $code
     * @return Iblock
     * @throws \Exception
     */
    public function addIblock($code) {
        if ($this->iblock) {
            throw new BuilderException('Iblock already set');
        }
        $this->iblock = new Iblock($code);
        return $this->iblock;
    }

    /**
     * @param $code
     * @return Iblock
     * @throws \Exception
     */
    public function updateIblock($code) {
        if ($this->iblock) {
            throw new BuilderException('Iblock already set');
        }
        if (!$data = $this->findIblock($code)) {
            throw new BuilderException("Can't find iblock with code {$code}");
        }
        $this->iblock = new Iblock($code, $data);
        return $this->iblock;
    }

    /**
     * @param $name
     * @return Property
     */
    public function addProperty($name) {
        $prop = new Property($name);
        $this->properties[] = $prop;
        return $prop;
    }

    /**
     * @param $name
     * @return Property
     * @throws BuilderException
     */
    public function updateProperty($name) {
        if (!$this->iblock->getId()) {
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
     * @throws BuilderException
     */
    public function commit() {
        global $DB;
        $DB->StartTransaction();
        try {
            $this->commitIblockType();

            $this->commitIblock();

            $this->commitProperties();
        } catch (\Exception $e) {
            $DB->Rollback();
            throw new BuilderException($e->getMessage());
        }
        $DB->Commit();
    }

    private function commitIblockType() {
        if (!$this->iblockType) {
            return;
        }
        $ibType = new \CIBlockType();
        if ($this->iblockType->iblockTypeId) {
            if (!$ibType->Update($this->iblockType->id, $this->iblockType->getSaveData())) {
                throw new BuilderException('IblockType was not updated. ' . $ibType->LAST_ERROR);
            }
        } else {
            $id = $ibType->Add($this->iblockType->getSaveData());
            $this->iblockType->setId($id);
        }
        if (!$this->iblockType->getId()) {
            throw new BuilderException('IblockType was not created. ' . $ibType->LAST_ERROR);
        }
    }

    private function commitIblock() {
        if (!$this->iblock) {
            return;
        }
        $ib = new \CIBlock();
        if ($this->iblock->getId()) {
            if (!$ib->Update($this->iblock->id, $this->iblock->getSaveData())) {
                throw new BuilderException('Iblock was not updated. ' . $ib->LAST_ERROR);
            }
        } else {
            $iblockId = $ib->Add($this->iblock->getSaveData());
            $this->iblock->setId($iblockId);
        }
        if (!$this->iblock->getId()) {
            throw new BuilderException('Iblock was not created. ' . $ib->LAST_ERROR);
        }
    }

    private function commitProperties() {
        $propertyGw = new \CIBlockProperty();
        if (empty($this->properties)) {
            return;
        }
        if (!$this->iblock->getId()) {
            throw new BuilderException("Iblock didn't set");
        }

        foreach ($this->properties as $property) {
            if ($property->getId() > 0) {
                continue;
            }
            $id = $propertyGw->Add(array_merge($property->getSaveData(), array('IBLOCK_ID' => $this->iblock->getId())));
            if (!$id) {
                throw new BuilderException("Property was {$property->name} not created. " . $propertyGw->LAST_ERROR);
            }
            $property->setId($id);

            $this->commitEnum($property);
        }

        foreach ($this->properties as $property) {
            if (!$property->getId()) {
                continue;
            }
            $id = $propertyGw->Update($property->id, array_merge($property->getSaveData(), array('IBLOCK_ID' => $this->iblock->getId())));
            if (!$id) {
                throw new BuilderException("Property was {$property->name} not updated. " . $propertyGw->LAST_ERROR);
            }
            $this->commitEnum($property);
        }
    }

    /**
     * @return Iblock
     */
    public function getIblock() {
        return $this->iblock;
    }

    private function findIblockType($type) {
        return \CIBlockType::GetList(null, array(
            'ID' => $type
        ))->Fetch();
    }

    private function findIblock($code) {
        return \CIBlock::GetList(null, array(
            '=CODE' => $code
        ))->Fetch();
    }

    private function findProperty($name) {
        return \CIBlockProperty::GetList(null, array(
            'NAME' => $name,
            'IBLOCK_ID' => $this->iblock->getId()
        ))->Fetch();
    }

    /**
     * @param Property $property
     * @throws BuilderException
     */
    private function commitEnum($property) {
        $obEnum = new \CIBlockPropertyEnum;
        foreach ($property->getEnumVariants() as $key => $variant) {
            if ($variant->del == 'Y' && $variant->getId() > 0) {
                $obEnum->Delete($variant->getId());
                continue;
            }
            if ($variant->getId() > 0) {
                if (!$obEnum->Update($variant->getId(), $variant->getSaveData())) {
                    throw new BuilderException("Failed to update enum '{$variant->value}'");
                }
                continue;
            }

            if (!$obEnum->Add(array_merge($variant->getSaveData(), array('PROPERTY_ID' => $property->getId())))) {
                throw new BuilderException("Failed to add enum '{$variant->value}'");
            }
        }

    }

}
