<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\Processes;

use WS\Migrations\ChangeDataCollector\CollectorFix;
use WS\Migrations\Entities\AppliedChangesLogModel;
use WS\Migrations\Module;
use WS\Migrations\Reference\ReferenceItem;
use WS\Migrations\SubjectHandlers\BaseSubjectHandler;

class AddProcess extends BaseProcess {

    public function update(BaseSubjectHandler $subjectHandler, CollectorFix $fix, AppliedChangesLogModel $log) {
        $data = $fix->getUpdateData();
        $result = $subjectHandler->applySnapshot($data);

        $referenceItem = new ReferenceItem();
        $referenceItem->id = $result->getId();
        $referenceItem->group = get_class($subjectHandler);
        $referenceItem->reference = $this->getReferenceController()
            ->getReferenceValueByOtherVersion($fix->getDbVersion(), $subjectHandler->getIdBySnapshot($data),get_class($subjectHandler));
        $this->getReferenceController()->registerItem($referenceItem);

        $data = $subjectHandler->getSnapshot($result->getId());

        $log->description = $fix->getName();
        $log->originalData = array();
        $log->updateData = $data;
        return $result;
    }

    public function rollback(BaseSubjectHandler $subjectHandler, AppliedChangesLogModel $log) {
        $id = $subjectHandler->getIdBySnapshot($log->updateData);
        return $subjectHandler->delete($id);
    }

    public function change(BaseSubjectHandler $subjectHandler, CollectorFix $fix, $data = array()) {
        $id = $subjectHandler->getIdByChangeMethod(Module::FIX_CHANGES_ADD_KEY, $data);

        $referenceItem = new ReferenceItem();
        $referenceItem->id = $id;
        $referenceItem->group = get_class($subjectHandler);
        $this->getReferenceController()->registerItem($referenceItem);

        $snapshot = $subjectHandler->getSnapshot($id);
        $fix
            ->setOriginalData(array())
            ->setUpdateData($snapshot);
    }

    public function getName() {
        return $this->getLocalization()->getDataByPath('add');
    }
}