<?php

namespace WS\Migrations\Builder\Entity;


abstract class Base {
    /** @var array */
    protected $data;

    public function __get($prop) {
        return $this->data[$prop];
    }

    public function __set($prop, $value) {
        $this->data[$prop] = $value;
    }

    public function getSaveData() {
        $map = $this->getMap();
        $fields = array();
        foreach ($this->data as $key => $value) {
            if (!isset($map[$key])) {
                $fields[$key] = $value;
                continue;
            }
            $fields[$map[$key]] = $value;
        }
        $fields = array_filter($fields);
        return $fields;
    }

    public function setSaveData($data) {
        $map = array_flip($this->getMap());
        foreach ($data as $key => $val) {
            if (!isset($map[$key])) {
                $this->{$key} = $val;
                continue;
            }
            $this->{$map[$key]} = $val;
        }
    }

    /**
     * @return array
     */
    public abstract function getMap();

}
