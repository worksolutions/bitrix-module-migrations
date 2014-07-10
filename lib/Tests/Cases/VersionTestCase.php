<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\Tests\Cases;


use WS\Migrations\SubjectHandlers\IblockHandler;
use WS\Migrations\Tests\AbstractCase;
use WS\Migrations\Tests\Mocks\ReferenceController;

class VersionTestCase extends AbstractCase {

    public function name() {
        return 'Версионирование платформ';
    }

    public function description() {
        return 'Тестирование функционала версионирования различных платформ';
    }
}