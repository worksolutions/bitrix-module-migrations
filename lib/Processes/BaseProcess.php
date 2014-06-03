<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\Processes;

use WS\Migrations\ChangeDataCollector\CollectorFix;

abstract class BaseProcess {

    abstract public function update(CollectorFix $fix);

    abstract public function rollback($log);
}
