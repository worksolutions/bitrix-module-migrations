<?php
return array(
    'main' => array(
        'title' => 'Migrations management',
        'list' => 'List migrations for apply',
        'version' => 'The current version of the database',
        'change_link' => 'change version',
        'errorList' => 'Unsuccessful applied migrations',
        'appliedList' => 'Successful applied migrations',
        'btnRollback' => 'Undo last change',
        'lastSetup' => array(
            'sectionName' => 'Last update :time: - :user:'
        ),
        'common' => array(
            'listEmpty' => 'List is empty'
        ),
        'newChangesDetail' => 'Changes list',
        'newChangesTitle' => 'New changes',
        'errorWindow' => 'Error info'
    ),
    'changeversion' => array(
        'title' => 'Change version of the database',
        'version' => 'The current version of the database',
        'setup' => 'setup',
        'owner' => 'Owner',
        'button_change' => 'Change',
        'description' => "
        In applying the \"Clean dump\", please change the version of the database, the current project will change the version that will function independently.
        ",
        'dialog' => array(
            'title' => 'Owner is set name'
        ),
        'otherVersions' => array(
            'tab' => 'Other versions'
        )
    ),
    'export' => array(
        'title' => 'Export migrations',
        'version' => 'The current version of the database'
    ),
    'import' => array(
        'title' => 'Import migrations',
        'version' => 'The current version of the database',
        'description' => "Transferring data schema

        In applying the \"Clean dump\", please apply import (without using the scheme), the current project will change the version that will function independently.

        The scheme used to create new data according to existing i.e. through the creation of new models of records in the event that relevant versions of imported records new data schema will not be created, they just modified according to imports.
        ",
        'fields' => array(
            'file' => 'Import file (version.json)',
            'isScheme' => 'Transfer scheme only'
        )
    ),
    'log' => array(
        'title' => 'Changes log',
        'fields' => array(
            'updateDate' => 'Update`s date',
            'description' => 'Description',
            'source' => 'Source',
            'dispatcher' => 'Dispatcher'
        ),
        'messages' => array(
            'InsertReference' => 'Insert another platform reference',
            'view' => 'Changes analysis',
            'pages' => 'Pages',
            'actualization' => 'Actualize',
            'descriptionMoreLink' => 'more',
            'errorWindow' => 'Error information'
        )
    ),
    'detail' => array(
        'title' => '#date. #source. Deployer - #deployer',
        'tabs' => array(
            'diff' => 'Diff',
            'final' => 'Final data',
            'merge' => 'Before changes data'
        ),
        'message' => array(
            'nobody' => 'Nobody'
        )
    ),
    'newChangesList' => array(
        'fields' => array(
            'date' => 'Created',
            'description' => 'Description',
            'source' => 'Source',
        ),
        'message' => array(
            "ago" => '\a\g\o',
            'view' => 'View'
        )
    ),
    'applyError' => array(
        'message' => 'Message',
        'data' => 'Data',
        'trace' => 'Trace',
        'error' => array(
            'modelNotExists' => 'Model by id=:id: not exists'
        )
    )
);