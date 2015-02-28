<?php

use WS\Migrations\Entities\AppliedChangesLogModel;

$fDiff = function ($array1, $array2) use (& $fDiff) {
    foreach($array1 as $key => $value) {
        if(is_array($value)) {
            if(!isset($array2[$key])) {
                $difference[$key] = $value;
            } elseif(!is_array($array2[$key])) {
                $difference[$key] = $value;
            } else {
                $new_diff = $fDiff($value, $array2[$key]);
                if($new_diff != false) {
                    $difference[$key] = $new_diff;
                }
            }
        } elseif(!isset($array2[$key]) || $array2[$key] != $value) {
            $difference[$key] = $value;
        }
    }
    return !isset($difference) ? 0 : $difference;
};

/** @var $localization \WS\Migrations\Localization */
$localization;
$module = \WS\Migrations\Module::getInstance();
$label = $_GET['label'];
/** @var AppliedChangesLogModel[] $models */
$models = AppliedChangesLogModel::find(array('filter' => array('=groupLabel' => $label)));
/** @var CMain $APPLICATION */
$models[0]
&& $APPLICATION->SetTitle(
    $localization->message('title', array(
        '#date' => $models[0]->date->format('d.m.Y'),
        '#source' => $models[0]->source,
        '#deployer' => $models[0]->getSetupLog()->shortUserInfo()
    ))
);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
$tabControl = new CAdminTabControl('ws_maigrations_label_detail', array(
    array(
        "DIV" => "edit1",
        "TAB" => $localization->getDataByPath('tabs.diff'),
        "ICON" => "iblock",
        "TITLE" => $localization->getDataByPath('tabs.diff'),
    ),
    array(
        "DIV" => "edit2",
        "TAB" => $localization->getDataByPath('tabs.final'),
        "ICON" => "iblock",
        "TITLE" => $localization->getDataByPath('tabs.final')
    ),
    array(
        "DIV" => "edit3",
        "TAB" => $localization->getDataByPath('tabs.merge'),
        "ICON" => "iblock",
        "TITLE" => $localization->getDataByPath('tabs.merge')
    )
));
$fSection = function ($label) {
    ?>
    <tr class="heading">
        <td colspan="2"><?=$label?></td>
    </tr>
    <?php
};
$fRow = function ($label, $value) {
    ?>
    <tr>
        <td width="30%"><b><?=$label?>:</b></td>
        <td width="60%"><?=$value?></td>
    </tr>
    <?php
};
$fRender = function ($data) use ($fRow, $fSection) {

};
ShowNote($localization->getDataByPath('description'));
$tabControl->Begin();
$tabControl->BeginNextTab();
foreach ($models as $model) {
    if (!$model->originalData) {
        $diff = $model->updateData;
    }
    if (!$model->updateData) {
        $diff = $model->originalData;
    }
    if ($model->updateData && $model->originalData) {
        $diff = $fDiff($model->updateData, $model->originalData);
    }
    if (!array_filter($diff)) {
        continue;
    }
    $fSection($model->description);
    foreach ($diff as $field => $value) {
        if (!$value) {
            continue;
        }
        if (is_array($value)) {
            if (!array_filter($value)) {
                continue;
            }
            $fSection($field);
            foreach ($value as $subValueField => $subValue) {
                if (!$subValue) {
                    continue;
                }
                $fRow($subValueField, $subValue);
            }
            continue;
        }
        $fRow($field, $value);
    }
}

$tabControl->BeginNextTab();
foreach ($models as $model) {
    if (!$model->updateData) {
        continue;
    }
    $fSection($model->description);
    foreach ($model->updateData as $field => $value) {
        if (!$value) {
            continue;
        }
        if (is_array($value)) {
            $fSection($field);
            foreach ($value as $subValueField => $subValue) {
                if (!$subValue) {
                    continue;
                }
                $fRow($subValueField, $subValue);
            }
            continue;
        }
        $fRow($field, $value);
    }
}
$tabControl->BeginNextTab();
foreach ($models as $model) {
    if (!$model->originalData) {
        continue;
    }
    $fSection($model->description);
    foreach ($model->originalData as $field => $value) {
        if (!$value) {
            continue;
        }
        if (is_array($value)) {
            $fSection($field);
            foreach ($value as $subValueField => $subValue) {
                if (!$subValue) {
                    continue;
                }
                $fRow($subValueField, $subValue);
            }
            continue;
        }
        $fRow($field, $value);
    }
}
$tabControl->EndTab();
$tabControl->Buttons();
$tabControl->End();
