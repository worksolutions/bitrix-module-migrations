<?php

$context = Bitrix\Main\Context::getCurrent();
/** @var \Bitrix\Main\HttpRequest $request */
$request = $context->getRequest();

$tester = \WS\Migrations\Module::getInstance()->getDiagnosticTester();
if ($request->isPost()) {
    $post = $request->getPostList()->toArray();
    $post = \Bitrix\Main\Text\Encoding::convertEncodingArray($post, "UTF-8", $context->getCulture()->getCharset());
    $post['run'] && $tester->run();
}
$lastResult = $tester->getLastResult();
/** @var $localization \WS\Migrations\Localization */
$localization;

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
?><form id="ws_maigrations_import" method="POST" action="<?=$APPLICATION->GetCurUri()?>" ENCTYPE="multipart/form-data" name="apply"><?
$form = new CAdminForm('ws_maigrations_diagnostic', array(
    array(
        "DIV" => "edit1",
        "TAB" => $localization->getDataByPath('title'),
        "ICON" => "iblock",
        "TITLE" => $localization->getDataByPath('title'),
    )
));
$module = \WS\Migrations\Module::getInstance();
$form->BeginPrologContent();
ShowNote($localization->getDataByPath('description'));
$form->EndPrologContent();
$form->Begin(array(
    'FORM_ACTION' => $APPLICATION->GetCurUri()
));

$form->BeginNextFormTab();
$form->BeginCustomField('version', 'vv');
?>
    <tr>
        <td width="30%"><?=$localization->getDataByPath('last.result')?>:</td>
        <td width="60%"><b><?=$lastResult->isSuccess() ? $localization->message('last.success') : $localization->message('last.fail')?> [<?=$lastResult->getTime()?>]</b></td>
    </tr>
    <tr>
        <td width="30%"><?=$localization->getDataByPath('last.description')?>:</td>
        <td width="60%">
<?php
    $fCreateUrlFromEntity = function ($type, $id) {
        $urlTemplate = '';
        switch ($type) {
            case 'iblock':
                $arIblock = CIBlock::GetArrayByID($id);
                $type = $arIblock['IBLOCK_TYPE_ID'];
                $urlTemplate = '/bitrix/admin/iblock_edit.php?type='.$type.'&ID='.$id;
                break;
            case 'iblockProperty':
                $arProperty = CIBlockProperty::GetByID($id)->Fetch();
                $iblockId = $arProperty['IBLOCK_ID'];
                $arIblock = CIBlock::GetArrayByID($iblockId);
                $type = $arIblock['IBLOCK_TYPE_ID'];
                $urlTemplate = '/bitrix/admin/iblock_edit.php?type='.$type.'&ID='.$iblockId;
                break;
            case 'iblockPropertyListValues':
                $arValue = \Bitrix\Iblock\PropertyEnumerationTable::getList(array('filter' => array('=ID' => $id)))->Fetch();
                $arProperty = CIBlockProperty::GetByID($arValue['PROPERTY_ID'])->Fetch();
                $iblockId = $arProperty['IBLOCK_ID'];
                $arIblock = CIBlock::GetArrayByID($iblockId);
                $type = $arIblock['IBLOCK_TYPE_ID'];
                $urlTemplate = '/bitrix/admin/iblock_edit.php?type='.$type.'&ID='.$iblockId;
                break;
            case 'iblockSection':
                $arSection = CIBlockSection::GetByID($id)->Fetch();
                $iblockId = $arSection['IBLOCK_ID'];
                $urlTemplate = '/bitrix/admin/iblock_section_edit.php?IBLOCK_ID='.$iblockId.'&ID='.$id;
                break;
        }
        return $urlTemplate;
    };
    $strings = array();
    foreach ($lastResult->getMessages() as $message) {
        if ($message->getType() == \WS\Migrations\Diagnostic\ErrorMessage::TYPE_ITEM_HAS_NOT_REFERENCE) {
            $url = $fCreateUrlFromEntity($message->getGroup(), $message->getItem());
            if ($url) {
                $strings[] = '<a href="'.$url.'">'.$message->getText().'</a>';
                continue;
            }
        }
        $strings[] = $message->getText();
    }
    echo implode('<br />', $strings);
?>
        </td>
    </tr>
    <tr>
        <td></td>
        <td><input type="submit" name="run" value="<?=$localization->message('run')?>"></td>
    </tr><?
$form->EndCustomField('version');
$form->BeginNextFormTab();
$form->Buttons();
$form->Show();
?></form>