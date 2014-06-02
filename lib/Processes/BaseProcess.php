<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\Processes;

abstract class BaseProcess {

    abstract public function update($log);

    abstract public function rollback($log);
}
