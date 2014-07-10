<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\Tests\Cases;


use WS\Migrations\SubjectHandlers\IblockHandler;
use WS\Migrations\Tests\AbstractCase;
use WS\Migrations\Tests\Mocks\ReferenceController;

class FixTestCase extends AbstractCase {

    public function name() {
        return '“естирование фиксаций изменений';
    }

    public function description() {
        return 'ѕроверка фиксации изменений при изменении структуры предметной области';
    }
}