<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\Tests\Cases;


use WS\Migrations\SubjectHandlers\IblockHandler;
use WS\Migrations\Tests\AbstractCase;

class IblockHandlerCase extends AbstractCase {

    public function name() {
        return 'Iblock Handler';
    }

    public function description() {
        return 'Iblock Handler Test';
    }

    public function testGetSnapshot() {
        $handler = new IblockHandler();
    }
}