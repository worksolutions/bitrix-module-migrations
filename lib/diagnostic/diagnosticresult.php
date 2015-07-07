<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\Diagnostic;

/**
 * Class DiagnosticResult
 *
 * @package WS\Migrations\Diagnostic
 */
class DiagnosticResult {

    /**
     * @var bool
     */
    private $success;

    /**
     * @var ErrorMessage[]
     */
    private $messages;

    /**
     * @var string
     */
    private $time;

    /**
     * @param bool $success
     * @param ErrorMessage[] $messages
     * @param string $time
     */
    public function __construct($success, array $messages, $time = '') {
        $this->success = $success;
        $this->messages = $messages;
        foreach ($this->messages as $message) {
            if (! $message instanceof ErrorMessage) {
                throw new \Exception('Message must be as object');
            }
        }
        $this->time = $time;
    }

    /**
     * @return DiagnosticResult
     */
    public static function createNull() {
        $class = new \ReflectionClass(get_called_class());
        $object = $class->newInstanceWithoutConstructor();
        $object->success = true;
        $object->messages = array();
        $object->time = '-';
        return $object;
    }

    /**
     * @return bool
     */
    public function isSuccess() {
        return $this->success;
    }

    /**
     * @return ErrorMessage[]
     */
    public function getMessages() {
        return $this->messages;
    }

    public function getMessagesText() {
        return array_map(function (ErrorMessage $message) {
            return $message->getText();
        }, $this->getMessages());
    }

    /**
     * @return string
     */
    public function getTime() {
        return $this->time;
    }
}