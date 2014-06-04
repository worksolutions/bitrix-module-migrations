<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\Processes;

use WS\Migrations\ChangeDataCollector\CollectorFix;
use WS\Migrations\Entities\AppliedChangesLogModel;
use WS\Migrations\Module;
use WS\Migrations\SubjectHandlers\BaseSubjectHandler;

class AddProcess extends BaseProcess {

    public function update(BaseSubjectHandler $subjectHandler, CollectorFix $fix) {
        $data = $fix->getData();
        $result = $subjectHandler->applySnapshot($data);
        $id = $subjectHandler->getIdByChangeMethod($data);

        $applyLog = new AppliedChangesLogModel();
        $applyLog->subjectName = get_class($subjectHandler);
        $applyLog->processName = get_class($this);
        $applyLog->description = $subjectHandler->getName().' - '.$id;
        $applyLog->originalData = array();
        $applyLog->updateData = $data;
        $applyLog->groupLabel = $fix->getLabel();
        $applyLog->save();

        return $result;
    }

    public function rollback(AppliedChangesLogModel $log) {
    }

    public function change(BaseSubjectHandler $subjectHandler, CollectorFix $fix, $data = array()) {
        $id = $subjectHandler->getIdByChangeMethod(Module::FIX_CHANGES_ADD_KEY, $data);
        $snapshot = $subjectHandler->getSnapshot($id);
        $fix
            ->setData($snapshot);

        $applyLog = new AppliedChangesLogModel();
        $applyLog->subjectName = get_class($subjectHandler);
        $applyLog->processName = get_class($this);
        $applyLog->description = $subjectHandler->getName().' - '.$id;
        $applyLog->originalData = array();
        $applyLog->updateData = $snapshot;
        $applyLog->groupLabel = $fix->getLabel();
        $applyLog->save();
    }

    public function getName() {
        return $this->getLocalization()->getDataByPath('add');
    }
}