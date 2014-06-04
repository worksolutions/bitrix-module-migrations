<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\Processes;

use WS\Migrations\ChangeDataCollector\CollectorFix;
use WS\Migrations\Entities\AppliedChangesLogModel;

abstract class BaseProcess {

    abstract public function update(CollectorFix $fix);

    abstract public function rollback(AppliedChangesLogModel $log);
}
