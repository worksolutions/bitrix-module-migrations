<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\Tests\Cases;


use WS\Migrations\SubjectHandlers\IblockHandler;
use WS\Migrations\Tests\AbstractCase;
use WS\Migrations\Tests\Mocks\ReferenceController;

class IblockHandlerCase extends AbstractCase {

    public function name() {
        return 'Iblock Handler';
    }

    public function description() {
        return 'Iblock Handler Test';
    }

    public function setUp() {
        \CModule::IncludeModule('iblock');
    }

    public function testGetSnapshot() {
        $handler = new IblockHandler(new ReferenceController());

        $arIblock = \CIBlock::getList()->fetch();
        $snapshot = $handler->getSnapshot($arIblock['ID']);

        $this->assertNotEmpty($snapshotIblock = $snapshot['iblock']);
        $this->assertEquals($snapshotIblock['ID'], $arIblock['ID']);
        $this->assertEquals($snapshotIblock['NAME'], $arIblock['NAME']);
        $this->assertNotEmpty($snapshot['type']);
    }
}