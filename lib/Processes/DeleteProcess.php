<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\Processes;


use WS\Migrations\ChangeDataCollector\CollectorFix;
use WS\Migrations\Entities\AppliedChangesLog;
use WS\Migrations\Module;
use WS\Migrations\SubjectHandlers\BaseSubjectHandler;

class DeleteProcess extends BaseProcess {
    private $_beforeChangesSnapshots = array();

    public function update(CollectorFix $fix) {
    }

    public function rollback(AppliedChangesLog $log) {
    }

    public function beforeChange(BaseSubjectHandler $subjectHandler, $data) {
        $id = $subjectHandler->getIdByChangeMethod(Module::FIX_CHANGES_BEFORE_DELETE_KEY, $data);
        $this->_beforeChangesSnapshots[$id] = $snapshot = $subjectHandler->getSnapshot($id);
    }

    public function afterChange(BaseSubjectHandler $subjectHandler, CollectorFix $fix, $data) {
        $id = $subjectHandler->getIdByChangeMethod(Module::FIX_CHANGES_AFTER_DELETE_KEY, $data);
        $fix
            ->setSubject($subjectHandler)
            ->setProcess($this)
            ->setData(array(
                'snapshot' => $this->_beforeChangesSnapshots[$id],
                'id' => $id
            ));
    }
}