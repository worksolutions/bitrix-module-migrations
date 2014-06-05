<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\Processes;

use WS\Migrations\ChangeDataCollector\CollectorFix;
use WS\Migrations\Entities\AppliedChangesLogModel;
use WS\Migrations\Module;
use WS\Migrations\SubjectHandlers\BaseSubjectHandler;

abstract class BaseProcess {
    public function getLocalization() {
        return Module::getInstance()->getLocalization('processes');
    }

    static public function className() {
        return get_called_class();
    }

    abstract public function getName();

    abstract public function update(BaseSubjectHandler $subjectHandler, CollectorFix $fix, AppliedChangesLogModel $log);

    abstract public function rollback(BaseSubjectHandler $subjectHandler, AppliedChangesLogModel $log);
}
