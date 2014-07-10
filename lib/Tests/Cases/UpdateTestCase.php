<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\Tests\Cases;


use WS\Migrations\SubjectHandlers\IblockHandler;
use WS\Migrations\Tests\AbstractCase;
use WS\Migrations\Tests\Mocks\ReferenceController;

class UpdateTestCase extends AbstractCase {

    public function name() {
        return 'ќбновление изменений';
    }

    public function description() {
        return '“естирование обновлени€ изменений согласно фиксаци€м';
    }
}