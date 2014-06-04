<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\Processes;


use WS\Migrations\ChangeDataCollector\CollectorFix;
use WS\Migrations\Entities\AppliedChangesLogModel;
use WS\Migrations\Module;
use WS\Migrations\SubjectHandlers\BaseSubjectHandler;

class DeleteProcess extends BaseProcess {
    private $_beforeChangesSnapshots = array();

    public function update(BaseSubjectHandler $subjectHandler, CollectorFix $fix) {
    }

    public function rollback(AppliedChangesLogModel $log) {
    }

    public function beforeChange(BaseSubjectHandler $subjectHandler, $data) {
        $id = $subjectHandler->getIdByChangeMethod(Module::FIX_CHANGES_BEFORE_DELETE_KEY, $data);
        $this->_beforeChangesSnapshots[$id] = $snapshot = $subjectHandler->getSnapshot($id);
    }

    public function afterChange(BaseSubjectHandler $subjectHandler, CollectorFix $fix, $data) {
        $id = $subjectHandler->getIdByChangeMethod(Module::FIX_CHANGES_AFTER_DELETE_KEY, $data);
        $fix
            ->setData(array(
                'snapshot' => $this->_beforeChangesSnapshots[$id],
                'id' => $id
            ));

        $applyLog = new AppliedChangesLogModel();
        $applyLog->subjectName = get_class($subjectHandler);
        $applyLog->processName = get_class($this);
        $applyLog->description = $subjectHandler->getName().' - '.$id;
        $applyLog->originalData = $this->_beforeChangesSnapshots[$id];
        $applyLog->updateData = $id;
        $applyLog->groupLabel = $fix->getLabel();
        $applyLog->save();

    }

    public function getName() {
        return $this->getLocalization()->getDataByPath('delete');
    }
}