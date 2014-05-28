<?php

namespace WS\Migrations;

/**
 * Class Module (Singletone)
 *
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */
class Module {

    private $localizePath = null,
            $localizations = array();

    private static $name = 'ws.migrations';

    private function __construct() {
        $this->localizePath = __DIR__.'/../lang/'.LANGUAGE_ID;
    }

    static public function getName($stripDots = false) {
        $res = static::$name;
        if ($stripDots) {
            $res = str_replace('.', '_', $res);
        }
        return $res;
    }

    /**
     * @return ModuleOptions
     */
    static public function getOptions() {
        return ModuleOptions::getInstance();
    }

    /**
     * @staticvar self $self
     * @return Module
     */
    static public function getInstance() {
        static $self = null;
        if (!$self) {
            $self = new self;
        }
        return $self;
    }

    static public function listen(){
        
    }

    /**
     * @param str $path
     * @throws \Exception
     * @return Localization
     */
    public function getLocalization($path) {
        if (!isset($this->localizations[$path])) {
            $realPath = $this->localizePath.'/'.str_replace('.', '/',$path).'.php';
            if (!  file_exists($realPath)) {
                throw new \Exception('localization by path not found');
            }
            $this->localizations[$path] = new Localization(include $realPath);
        }
        return $this->localizations[$path];
    }
}
