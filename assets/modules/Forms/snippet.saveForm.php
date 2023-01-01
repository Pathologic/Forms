<?php
if (!isset($FormLister)) return;
include_once (MODX_BASE_PATH . 'assets/modules/Forms/lib/model.php');
$type = $FormLister->getCFGDef('saveFormType');
$fieldNames = $FormLister->getCFGDef('saveFormFields');
$defaultNames = $FormLister->getCFGDef('saveFormDefaults');
$fields = $FormLister->config->loadArray($fieldNames, '');
$defaultNames = $FormLister->config->loadArray($defaultNames, '');
if (!empty($type)) {
    $data = new Forms\Model($modx);
    $data->create([
        'type' => $type,
        'name' => $FormLister->getField(isset($defaultNames['name']) ? $defaultNames['name'] : 'name'),
        'phone' => FormLister\Filters::numeric($FormLister->getField(isset($defaultNames['phone']) ? $defaultNames['phone'] :'phone')),
        'email' => $FormLister->getField(isset($defaultNames['email']) ? $defaultNames['email'] : 'email')
    ]);
    foreach ($fields as $field => $name) {
        if (!is_scalar($name)) continue;
        $data->set($name, $FormLister->getField($field));
    }
    $data->setFormFields(array_values($fields));
    if ($id = $data->save()) {
        $FormLister->setField('form.id', $id);
    }
}
