<?php

namespace WS\Migrations\Builder;

use WS\Migrations\Builder\Entity\Form;
use WS\Migrations\Builder\Entity\FormField;

class FormBuilder {
    /** @var  Form */
    private $form;
    /** @var  FormField[] */
    private $fields;

    public function __construct() {
        \CModule::IncludeModule('form');
    }

    public function reset() {
        $this->form = null;
    }

    /**
     * @param $name
     * @param $sid
     * @return Form
     * @throws BuilderException
     */
    public function addForm($name, $sid) {
        if ($this->form) {
            throw new BuilderException('Form already set');
        }
        $this->form = new Form($name, $sid);
        return $this->form;
    }

    /**
     * @param $sid
     * @return Form
     * @throws BuilderException
     */
    public function updateForm($sid) {
        if ($this->form) {
            throw new BuilderException('Form already set');
        }
        $formData = $this->findForm($sid);
        $this->form = new Form($formData['NAME'], $sid, $formData);
        return $this->form;
    }

    /**
     * @param $sid
     * @return FormField
     * @throws BuilderException
     */
    public function addField($sid) {
        if (!$this->form) {
            throw new BuilderException("Form doesn't set");
        }
        $field = new FormField($sid);
        $this->fields[] = $field;
        return $field;
    }

    /**
     * @param $sid
     * @return FormField
     * @throws BuilderException
     */
    public function updateField($sid) {
        if (!$this->form) {
            throw new BuilderException("Form doesn't set");
        }
        $data = $this->findField($sid);
        $field = new FormField($sid, $data);
        $this->fields[] = $field;
        return $field;
    }

    /**
     * @throws BuilderException
     */
    public function commit() {
        global $DB;
        $DB->StartTransaction();
        try {
            $this->commitForm();
            $this->commitFields();
        } catch (\Exception $e) {
            $DB->Rollback();
            throw new BuilderException($e->getMessage());
        }
        $DB->Commit();
    }

    private function findForm($sid) {
        $data = \CForm::GetList($by = 'ID', $order = 'ASC', array(
            'SID' => $sid
        ), $isFiltered)->Fetch();

        if (!$data) {
            throw new BuilderException("Form '{$sid}' not found");
        }

        return $data;
    }

    /**
     * @throws BuilderException
     */
    private function commitForm() {
        global $strError;
        $formId = \CForm::Set($this->form->getSaveData(), $this->form->getId(), 'N');
        if (!$formId) {
            throw new BuilderException("Form wasn't saved. " . $strError);
        }
        $this->form->setId($formId);
    }

    private function commitFields() {
        global $strError;
        $gw = new \CFormField();
        foreach ($this->fields as $field) {
            $saveData = $field->getSaveData();
            $saveData['FORM_ID'] = $this->form->getId();
            $fieldId = $gw->Set($saveData, $field->getId(), 'N', 'Y');
            if (!$fieldId) {
                throw new BuilderException("Field '{$field->sid}' wasn't saved. " . $strError);
            }
            $field->setId($fieldId);
        }
    }

    private function findField($sid) {
        if (!$this->form->getId()) {
            throw new BuilderException("Form doesn't set");
        }
        $field = \CFormField::GetList($this->form->getId(), 'ALL', $by, $order, array(
            'SID' => $sid,
        ), $isFiltered)->Fetch();

        return $field;
    }
}
