<?php

namespace WS\Migrations\Builder;

use Bitrix\Main\Mail\Internal\EventMessageTable;
use WS\Migrations\Builder\Entity\EventMessage;
use WS\Migrations\Builder\Entity\EventType;

class EventsBuilder {
    /** @var  EventType */
    private $eventType;
    /** @var  EventMessage[] */
    private $newMessages;
    /** @var  EventMessage[] */
    private $exitsMessages;

    public function reset() {
        $this->eventType = null;
        $this->newMessages = array();
        $this->exitsMessages = array();
    }

    /**
     * @param $type
     * @param $lid
     * @return EventType
     * @throws BuilderException
     */
    public function addEventType($type, $lid) {
        if ($this->eventType) {
            throw new BuilderException('EventType already set');
        }
        $this->eventType = new EventType($type, $lid);
        return $this->eventType;
    }

    /**
     * @param $type
     * @param $lid
     * @return EventType
     * @throws BuilderException
     */
    public function updateEventType($type, $lid) {
        if ($this->eventType) {
            throw new BuilderException('EventType already set');
        }
        $this->eventType = new EventType($type, $lid, $this->findEventType($type, $lid));
        return $this->eventType;
    }

    public function addEventMessage($from, $to, $siteId) {
        $message = new EventMessage($from, $to, $siteId);
        $this->newMessages[] = $message;
        return $message;
    }

    public function updateEventMessages() {
        foreach ($this->findMessages() as $data) {
            $this->exitsMessages[] = new EventMessage(false, false, false, $data);
        }
        return $this->exitsMessages;
    }
    /**
     * @return EventType
     */
    public function getEventType() {
        return $this->eventType;
    }

    /**
     * @throws BuilderException
     */
    public function commit() {
        global $DB;
        $DB->StartTransaction();
        try {
            $this->commitEventType();
            $this->commitNewEventMessages();
            $this->commitExistsEventMessages();
        } catch (\Exception $e) {
            $DB->Rollback();
            throw new BuilderException($e->getMessage());
        }
        $DB->Commit();
    }

    /**
     * @param $type
     * @param $lid
     * @return array
     * @throws BuilderException
     */
    private function findEventType($type, $lid) {
        $data = \CEventType::GetList(array(
            'TYPE_ID' => $type,
            'LID' => $lid
        ))->Fetch();
        if (empty($data)) {
            throw new BuilderException("EventType '{$type}' not found for lid '{$lid}'");
        }
        return $data;
    }

    /**
     * @throws BuilderException
     */
    private function commitEventType() {
        global $APPLICATION;
        if (!$this->eventType) {
            throw new BuilderException("EventType doesn't set");
        }
        $gw = new \CEventType();
        if ($this->eventType->getId() > 0) {
            $gw->Update(['ID' => $this->eventType->getId()], $this->eventType->getSaveData());
        } else {
            $res = $gw->Add($this->eventType->getSaveData());
            if (!$res) {
                throw new BuilderException('EventType add failed with error: ' . $APPLICATION->GetException()->GetString());
            }
            $this->eventType->setId($res);
        }
    }

    private function commitNewEventMessages() {
        global $APPLICATION;
        if (!$this->getEventType()->getId()) {
            throw new BuilderException("EventType doesn't set");
        }
        $gw = new \CEventMessage();
        foreach ($this->newMessages as $message) {
            $id = $gw->Add(array_merge(
                $message->getSaveData(),
                array('EVENT_NAME' => $this->getEventType()->eventName)
            ));
            if (!$id) {
               throw new BuilderException("EventMessage add failed with error: " . $APPLICATION->GetException()->GetString());
            }
            $message->setId($id);
        }
    }

    private function commitExistsEventMessages() {
        global $APPLICATION;
        if (!$this->getEventType()->getId()) {
            throw new BuilderException("EventType doesn't set");
        }
        $gw = new \CEventMessage();
        foreach ($this->exitsMessages as $message) {
            if (!$message->isRemoved()) {
               continue;
            }
            if (!$gw->Delete($message->getId())) {
                throw new BuilderException("EventType wasn't deleted: ". $APPLICATION->GetException()->GetString());
            }
        }
        foreach ($this->exitsMessages as $message) {
            if ($message->isRemoved()) {
                continue;
            }
            if (!$gw->Update($message->getId(), $message->getSaveData())) {
                throw new BuilderException("EventType wasn't updated: ". $APPLICATION->GetException()->GetString());
            }
        }
    }

    private function findMessages() {
        if (!$this->getEventType()->getId()) {
            throw new BuilderException("EventType doesn't set");
        }
        $res = EventMessageTable::getList(array(
            'filter' => array(
                'EVENT_NAME' => $this->getEventType()->eventName
            )
        ));
        return $res->fetchAll();
    }


}
