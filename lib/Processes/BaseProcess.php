<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\Processes;

use WS\Migrations\ChangeDataCollector\CollectorFix;
use WS\Migrations\Entities\AppliedChangesLog;

abstract class BaseProcess {

    abstract public function update(CollectorFix $fix);

    abstract public function rollback(AppliedChangesLog $log);
}
