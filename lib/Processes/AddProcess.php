<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\Processes;

use WS\Migrations\ChangeDataCollector\CollectorFix;
use WS\Migrations\Entities\AppliedChangesLog;
use WS\Migrations\Module;
use WS\Migrations\SubjectHandlers\BaseSubjectHandler;

class AddProcess extends BaseProcess {

    public function update(CollectorFix $fix) {
    }

    public function rollback(AppliedChangesLog $log) {
    }

    public function change(BaseSubjectHandler $subjectHandler, CollectorFix $fix, $data = array()) {
        $id = $subjectHandler->getIdByChangeMethod(Module::FIX_CHANGES_ADD_KEY, $data);
        $snapshot = $subjectHandler->getSnapshot($id);
        $fix
            ->setProcess(get_class($this))
            ->setSubject(get_class($subjectHandler))
            ->setData($snapshot);

        $applyLog = new AppliedChangesLog();
        $applyLog->subjectName = get_class($subjectHandler);
        $applyLog->processName = get_class($this);
        $applyLog->description = $subjectHandler->getName().' - '.$id;
        $applyLog->originalData = array();
        $applyLog->updateData = $snapshot;
        $applyLog->groupLabel = $fix->getLabel();
        $applyLog->save();
    }
}