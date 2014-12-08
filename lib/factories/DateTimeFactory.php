<?php
/**
 * Created by JetBrains PhpStorm.
 * User: under5
 * Date: 08.12.14
 * Time: 12:05
 * To change this template use File | Settings | File Templates.
 */

namespace WS\Migrations\factories;


class DateTimeFactory {

    static public function create($time = null) {
        return new \DateTime($time, new \DateTimeZone(date_default_timezone_get() ?: 'Europe/Moscow'));
    }
}