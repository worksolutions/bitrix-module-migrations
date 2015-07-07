<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\Diagnostic;

use WS\Migrations\Module;
use WS\Migrations\SubjectHandlers\BaseSubjectHandler;

class DiagnosticTester {

    const LOG_TYPE = 'WS_MIGRATIONS_DIAGNOSTIC';

    /**
     * @var BaseSubjectHandler[]
     */
    private $handlers;
    /**
     * @var Module
     */
    private $module;

    /**
     * @param BaseSubjectHandler[] $handlers
     * @param Module $module
     */
    public function __construct(array $handlers, Module $module) {
        $this->handlers = $handlers;
        $this->module = $module;
    }

    /**
     * @return bool
     */
    public function run() {
        $success = true;
        $messages = array();

        if (!$this->module->isValidVersion()) {
            $messages[] = new ErrorMessage('module', '', '', 'Module has not valid version');
        }
        foreach ($this->handlers as $handler) {
            $handlerResult = $handler->diagnostic();
            if (!$handlerResult->isSuccess()) {
                $success = false;
                $messages = array_merge($messages, $handlerResult->getMessages());
            }
        }

        $jsonData = json_encode(array(
            'success' => $success,
            'messages' => array_map(function (ErrorMessage $message) {
                return $message->toArray();
            }, $messages)
        ));
        \CEventLog::Log('INFO', self::LOG_TYPE, 'ws.migrations', null, $jsonData);
        return true;
    }

    /**
     * @return DiagnosticResult
     */
    public function getLastResult() {
        $arLog = \CEventLog::GetList(array('ID' => 'DESC'), array(
                'AUDIT_TYPE_ID' => self::LOG_TYPE
            ),
            array(
                'nPageSize' => 1
            )
        )->Fetch();

        if (!$arLog) {
            return DiagnosticResult::createNull();
        }
        $arLogData = $arLog ? json_decode($arLog['DESCRIPTION'], true) : array();
        $res = new DiagnosticResult(
            $arLogData['success'] ?: false,
            array_map(
                function ($messageData) {
                    return ErrorMessage::unpack($messageData);
                },
                $arLogData['messages'] ?: array()
            ),
            $arLog['TIMESTAMP_X']
        );
        return $res;
    }
}