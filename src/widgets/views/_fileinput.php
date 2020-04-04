<?php

use yii\helpers\Html;

\mix8872\yiiFiles\assets\FileinputAsset::register($this);
$options = [
    'title' => 'Выбрать файл',
    'accept' => $inputFileTypes,
    'multiple' => $multiple,
    'data-id' => $uniqueName,
];
?>
<?php if (!$field): ?>
    <label class="control-label" for="<?= $uniqueName ?>"><?= $label ?></label>
    <?= Html::activeInput('file', $model, $attribute, $options) ?>
<?php else: ?>
    <?= Html::activeInput('file', $model, $attribute, $options) ?>
<?php endif; ?>
<?php
$this->registerJs("
    (function($){
        $(function(){
            $('input[data-id=" . $uniqueName . "]').fileinput({
                showUpload: false,
                minFileCount: 0,
                language: 'ru',
                previewFileType:'any',
                browseLabel: '',
                removeLabel: '',
                theme: 'fa',
                " . ($theme === \mix8872\yiiFiles\widgets\FilesWidget::THEME_BROWSE_DRAGDROP ? '
                browseOnZoneClick: true,
                ' : '
                dropZoneEnabled: false,
                ') . "
                mainClass: 'input-group',
                allowedFileTypes: JSON.parse('" . $jsAllowedFileTypes . "'),
                allowedFileExtensions: JSON.parse('" . $jsAllowedFileExtensions . "'),
                browseClass: 'btn btn-secondary'
            });
        });
    }(jQuery));
");
?>
