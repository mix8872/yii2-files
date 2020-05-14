<?php

use yii\helpers\Html;

\mix8872\yiiFiles\assets\DragdropAsset::register($this);

$options = [
    'title' => 'Выбрать файл',
    'accept' => $inputFileTypes,
    'multiple' => $multiple,
    'data' => [
        'id' => $uniqueName,
        'height' => $height,
        'width' => $width,
    ]
];
if (!$multiple && $file = $query->one()) {
    $options['data']['default-file'] = $file->url;
    $options['data']['show-remove'] = 'true';
}
?>
<?php if (!$field): ?>
    <label class="control-label" for="<?= $uniqueName ?>"><?= $label ?></label>
    <?= Html::activeInput('file', $model, $attribute, $options) ?>
<?php else: ?>
    <?= Html::activeInput('file', $model, $attribute, $options) ?>
<?php endif; ?>
<?php
$this->registerJs("
    $('input[data-id=" . $uniqueName . "]').DropifyMultiple({
        messages: {
            'default': 'Перетащите сюда файл или кликните для выбора',
            'replace': 'Перетащите сюда файл или кликните для замены',
            'remove':  'отмена',
        }
    });
");
