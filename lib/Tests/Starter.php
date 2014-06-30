<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\Tests;


use WS\Migrations\Tests\Cases\ErrorException;

class Starter {

    const SECTION = 'WSMIGRATIONS';

    static public function className() {
        return get_called_class();
    }

    static public function cases() {
        return array(
            \WS\Migrations\Tests\Cases\SimpleCase::className()
        );
    }

    /**
     * Run module tests
     * @internal param $aCheckList
     * @return array
     */
    static public function items() {
        $points = array();
        $i = 1;
        $fGetCaseId = function ($className) {
            $arClass = implode('\\', $className);
            return array_pop($arClass);
        };
        foreach (self::cases() as $caseClass) {
            $points[self::SECTION] = array(
                'AUTO' => 'Y',
                'NAME' => $caseClass::name(),
                'DESC' => $caseClass::description(),
                'CLASS_NAME' => get_called_class(),
                'METHOD_NAME' => 'run',
                'PARENT' => self::SECTION,
                'PARAMS' => array(
                    'class' => $caseClass
                )
            );
        }

        return array(
            'CATEGORIES' => array(
                self::SECTION => array(
                    'NAME' => 'Рабочие решения. Модуль миграций'
                )
            ),
            'POINTS' => $points
        );
    }

    static public function run($params) {
        $class = $params['class'];
        $result = new Result();
        if (!$class) {
            $result->setSuccess(false);
            $result->setMessage('Params not is correct');
            return $result->toArray();
        }
        $testCase = new $class();
        if (!$testCase instanceof AbstractCase) {
            $result->setSuccess(false);
            $result->setMessage('Case class is not correct');
            return $result->toArray();
        }
        $refClass = new \ReflectionObject($testCase);
        $testMethods  = array_filter($refClass->getMethods(), function (\ReflectionMethod $method) {
            return strpos(strtolower($method->getName()), 'test') === 0;
        });
        try {
            /** @var $method \ReflectionMethod */
            foreach ($testMethods as $method) {
                $method->invoke($testCase);
            }
        } catch (ErrorException $e) {
            return $result->setSuccess(false)
                ->setMessage($e->getMessage()."\n".$e->getTraceAsString())
                ->toArray();
        }
        return $result->setSuccess(true)
            ->setMessage('Tests `OK')
            ->toArray();
    }
}