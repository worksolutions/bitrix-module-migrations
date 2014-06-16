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

    public function update(BaseSubjectHandler $subjectHandler, CollectorFix $fix, AppliedChangesLogModel $log) {
        $data = $fix->getUpdateData();
        $id = $subjectHandler->getIdBySnapshot($data);
        $originalData = $subjectHandler->getSnapshot($id);

        $refItem = $this->getReferenceController()
            ->getItemByOtherVersion($fix->getDbVersion(), $id, get_class($subjectHandler));

        $id = $refItem->id;

        $result = $subjectHandler->delete($id);

        $log->description = $fix->getName();
        $log->originalData = $originalData;
        $log->updateData = $data;

        return $result;
    }

    public function rollback(BaseSubjectHandler $subjectHandler, AppliedChangesLogModel $log) {
        return $subjectHandler->applySnapshot($log->originalData);
    }

    public function beforeChange(BaseSubjectHandler $subjectHandler, $data) {
        $id = $subjectHandler->getIdByChangeMethod(Module::FIX_CHANGES_BEFORE_DELETE_KEY, $data);
        $this->_beforeChangesSnapshots[$id] = $snapshot = $subjectHandler->getSnapshot($id);
    }

    public function afterChange(BaseSubjectHandler $subjectHandler, CollectorFix $fix, $data) {
        $id = $subjectHandler->getIdByChangeMethod(Module::FIX_CHANGES_AFTER_DELETE_KEY, $data);
        $fix
            ->setOriginalData($this->_beforeChangesSnapshots[$id])
            ->setUpdateData($id);
    }

    public function getName() {
        return $this->getLocalization()->getDataByPath('delete');
    }
}