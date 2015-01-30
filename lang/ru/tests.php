<?php
return array(
    'run' => array(
        'name' => 'Рабочие решения. Модуль миграций',
        'report' => array(
            'completed' => 'Успешно пройдено',
            'assertions' => 'Проверок'
        )
    ),
    'cases' => array(
        \WS\Migrations\Tests\Cases\FixTestCase::className() => array(
            'name' => 'Тестирование фиксаций изменений',
            'description' => 'Проверка фиксации изменений при изменении структуры предметной области',
            'errors' => array(
                'not create iblock id' => 'Не создан идентификатор инфоблока. :lastError',
                'not create property iblock id' => 'Не создано свойство инфоблока. :lastError',
                'not create section iblock id' => 'Не создана секция инфоблока. :lastError',
                'last log records need been update process' => 'Последними записями лога должен быть процесс добавления',
                'iblock not registered after update' => 'Инфоблок незарегистрирован в обновлении, тут :actual, нужен :need',
                'property iblock not registered after update' => 'Свойство незарегистрировано в обновлении, оригинал - :original, получено - :actual',
                'section iblock not registered after update' => 'Секция незарегистрирована в обновлении, оригинал - :original, получено - :actual',
                'links expected count' => 'Ссылок должно быть :count',
                'error update result' => 'Результат обновления отрицательный',
                'having one fixing updates' => 'Наличие одной фиксации обновления',
                'fixing name change' => 'Фиксация на изменение имени',
                'iblock must be removed from the database' => 'Инфоблок должен быть удален из БД',
                'uninstall entries must be: section, property information, iblock' => 'Должны быть записи удалений: секция, свойство, инфоблок',
                'should be uninstall entries: Section' => 'Должны быть записи удалений: секция',
                'should be uninstall entries: Property' => 'Должны быть записи удалений: свойство инфоблока',
                'should be uninstall entries: Iblock' => 'Должны быть записи удалений: инфоблок',
                'data pack when you remove the section must be an identifier' => 'Данными обновления при удалении секции должен быть идентификатор, а тут - :value',
                'data pack when you remove the property must be an identifier' => 'Данными обновления при удалении свойства инфолбока должен быть идентификатор, а тут - :value',
                'data pack when you remove the iblock must be an identifier' => 'Данными обновления при удалении инфолбока должен быть идентификатор, а тут - :value',
                'data should be stored remotely information block' => 'Должны хранится данные удаленного инфоблока',
                'should be in an amount of writable' => 'Должны быть доступены записи в количестве: :count',
                'logging process should be - Disposal' => 'Журналируемый процесс должен быть - удалением',
                'information block data to be restored' => 'Данные инфоблока должны быть восстановлены',
                'iblock restored identifier changed' => 'Инфоблок восстановлен, идентификатор изменен',
                'must present properties of reduced information iblock' => 'Должны присутствовать свойства восстановленного инфоблока - :iblockId',
                'must present sections of reduced information iblock' => 'Должны присутствовать секции(разделы) восстановленного инфоблока  - :iblockId',
            )
        ),
        \WS\Migrations\Tests\Cases\InstallTestCase::className() => array(
            'name' => 'Тестирование процедуры установки',
            'description' => '',
            'errors' => array(
                'number of links to the information block and the information block entries must match' => 'Количество ссылок по инфоблокам и записей инфоблоков должно совпадать',
                'number of links on the properties of information blocks and records must match' => 'Количество ссылок по свойствам инфоблоков и записей должно совпадать',
                'number of links to information block sections and records must match' => 'Количество ссылок по разделам инфоблоков и записей должно совпадать',
            )
        ),
        \WS\Migrations\Tests\Cases\UpdateTestCase::className() => array(
            'name' => 'Обновление изменений',
            'description' => 'Тестирование обновления изменений согласно фиксациям',
            'errors' => array(
                'record IB must be present' => 'Запись ИБ должна присутствовать',
                'not also recording information block' => 'Не добавилась запись инфоблока',
                'unavailable identifier of the new information block' => 'Недоступен идентификатор нового инфоблока',
                'added properties not available information block' => 'Недоступны добавленные свойства информационного блока, ИБ ID - :iblockId',
                'added sections not available information block' => 'Недоступны добавленные секции информационного блока',
                'inconsistencies initialization name' => 'Недоступны добавленные секции информационного блока',
                'name information block has not changed' => 'Имя инфоблока не изменилось',
                'section should not be' => 'Секции быть не должно',
                'in the information block is only one property' => 'У инфоблока остается только одно свойство',
                'iblock not been deleted' => 'Инфоблок не был удален',
                'iblock exists' => 'Инфоблок существует',
                'requires fixations adding links' => 'Необходимо наличие фиксаций добавления ссылок',
                'when upgrading recorded only links' => 'При обновлении регистрируются только ссылки',
            )
        ),
        \WS\Migrations\Tests\Cases\RollbackTestCase::className() => array(
            'name' => 'Откат изменений',
            'description' => '',
            'errors' => array(
            )
        )
    )
);