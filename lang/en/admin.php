<?php
return array(
    'main' => array(
        'title' => 'Migrations management',
        'list' => array(
            'auto' => 'New auto migrations',
            'scenarios' => 'New scenarios'
        ),
        'version' => 'The current version of the database',
        'change_link' => 'change version',
        'errorList' => 'Unsuccessful applied migrations',
        'appliedList' => 'Successful applied migrations',
        'btnRollback' => 'Undo last change',
        'lastSetup' => array(
            'sectionName' => 'Last update :time: - :user:'
        ),
        'common' => array(
            'listEmpty' => 'List is empty',
            'pageEmpty' => 'Data for update don`t exists yet'
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
    ),
    'entitiesVersions' => array(
        'title' => 'Versions of data',
        'fields' => array(
            'reference' => 'Reference',
            'versions' => 'Id by versions',
            'destination' => 'Entity'
        ),
        'messages' => array(
            'pages' => 'Pages'
        ),
        'subjects' => array(
            'iblock' => 'Iblock',
            'iblockProperty' => 'Iblock Property',
            'iblockSection' => 'Iblock Section',
        )
    ),
    'createScenario' => array(
        'title' => 'Scenario',
        'field' => array(
            'name' => 'Name',
            'description' => 'Description'
        ),
        'path-to-file' => 'Migration class places in file #path#',
        'save-file-error' => 'File has not saved'
    ),
    'diagnostic' => array(
        'title' => 'Diagnostic',
        'description' => 'Diagnostic state, tips of problem',
        'last' => array(
            'description' => 'Errors',
            'result' => 'Result',
            'success' => 'Success',
            'fail' => 'Error'
        ),
        'run' => 'Start diagnostic',
    ),
    'log' => array(
        'title' => 'Changes log',
        'fields' => array(
            'updateDate' => 'Date',
            'description' => 'Update description',
            'source' => 'Source',
            'dispatcher' => 'Updated'
        ),
        'messages' => array(
            'InsertReference' => 'Insert another platform reference',
            'view' => 'Changes analyse',
            'pages' => 'Pages',
            'actualization' => 'Actualize',
            'descriptionMoreLink' => 'more',
            'errorWindow' => 'Error information'
        )
    )
);