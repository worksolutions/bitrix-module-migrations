<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\Tests\Cases;


use WS\Migrations\Tests\AbstractCase;

class SimpleCase extends AbstractCase{

    public function name() {
        return 'Тестовая проба';
    }

    public function description() {
        return 'Представление некой прослойки тестирования';
    }

    public function testError() {
//        $this->assertTrue(false);
    }

    public function testSuccess() {
        $this->assertTrue(true);
    }
}