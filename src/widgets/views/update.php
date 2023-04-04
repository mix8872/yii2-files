<?php

use himiklab\sortablegrid\SortableGridView;
use mix8872\yiiFiles\widgets\FilesWidget;
use yii\helpers\Html;
use yii\helpers\Url;

$uniqueName = 'i' . time();
$afterUploadUrl = Url::to(['/files/default/update', 'id' => $model->id]);
$setsSortableAction = Url::to(['/files/default/sort-sets']);
$js = <<<JS
$('input[data-id=$uniqueName]').on("filebatchuploadsuccess", function() {
    $.pjax.reload('#file-sets-upload', {
        timeout: 2000,
        replaceRedirect: false,
        push: false,
        replace: false,
        url: '$afterUploadUrl',
        container: '#file-sets-upload',
        fragment: '#file-sets-upload'
    });
    $('input[data-id=$uniqueName]').fileinput('reset');
});
$('a.lightbox').magnificPopup({
    type: 'image',
});
JS;
$this->registerJs($js);
?>
<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header">
            <h4 class="modal-title">Свойства файла <?= mb_strimwidth($model->name, 0, 30, ' ...') ?></h4>
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        </div>
        <div class="modal-body">
            <ul class="nav nav-tabs navtab-bg nav-justified">
                <?php $i = 0; ?>
                <?php foreach ($languages as $key => $lang):
                    if (preg_match('/\w{2}-\w{2}/ui', $lang)) {
                        $lang = strtolower(preg_replace('/(\w{2})-(\w{2})/ui', "\$1", $lang));
                    }
                    ?>
                    <li class="<?= $i++ == 0 ? 'active' : '' ?>">
                        <a href="#tab-<?= $lang ?>" data-toggle="tab"
                           aria-expanded="<?= $i == 0 ? 'true' : 'false' ?>">
                            <?= $lang ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <div class="tab-content">
                <?php $i = 0; ?>
                <?php foreach ($languages as $key => $lang):
                    if (preg_match('/\w{2}-\w{2}/ui', $lang)) {
                        $lang = strtolower(preg_replace('/(\w{2})-(\w{2})/ui', "\$1", $lang));
                    }
                    $content = $model->getLangContent($lang); ?>
                    <div class="tab-pane<?= $i++ == 0 ? ' active' : '' ?>" id="tab-<?= $lang ?>">
                        <?php if (preg_match('/image/ui', $model->mime_type)): ?>
                            <div class="form-group">
                                <label class="control-label"
                                       for="file-<?= $content->id ?>-name">Name</label>
                                <?= Html::activeTextInput($content, '[' . $content->id . ']name', ['class' => 'form-control']) ?>
                            </div>
                            <div class="form-group">
                                <label class="control-label"
                                       for="file-<?= $content->id ?>-title">Title</label>
                                <?= Html::activeTextInput($content, '[' . $content->id . ']title', ['class' => 'form-control']) ?>
                            </div>
                        <?php endif; ?>
                        <div class="form-group">
                            <label class="control-label" for="file-<?= $content->id ?>-description">Description</label>
                            <?= Html::activeTextInput($content, '[' . $content->id . ']description', ['class' => 'form-control']) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="file-set">
                <?= $this->render('_fileinput', [
                    'uniqueName' => $uniqueName,
                    'label' => 'Добавить файлы в набор',
                    'model' => $setModel,
                    'attribute' => 'attachment[]',
                    'multiple' => true,
                    'inputFileTypes' => $inputFileTypes,
                    'jsAllowedFileTypes' => json_encode($jsAllowedFileTypes),
                    'jsAllowedFileExtensions' => json_encode($jsAllowedFileExtensions),
                    'theme' => FilesWidget::THEME_BROWSE,
                    'field' => null,
                    'uploadUrl' => Url::to(['/files/default/sets-upload', 'id' => $model->id])
                ]) ?>
                <div id="file-sets-upload">
                    <?= SortableGridView::widget([
                        'dataProvider' => $setsDataProvider,
                        'rowOptions' => function ($model) {
                            return ['id' => $model->id];
                        },
                        'id' => 'sets-list',
                        'showOnEmpty' => false,
                        'emptyText' => '',
                        'sortableAction' => $setsSortableAction,
                        'rowOptions' => [
                            'class' => 'file_row'
                        ],
                        'columns' => [
                            [
                                'attribute' => 'filename',
                                'format' => 'raw',
                                'value' => function ($setsModel) use ($model) {
                                    if (preg_match("/^image\/.+$/i", $model->mime_type)) {
                                        return Html::a(
                                            Html::img($setsModel->url, ['width' => '40px']),
                                            $setsModel->url,
                                            ['class' => 'lightbox']
                                        );
                                    } elseif (preg_match("/^video\/.+$/i", $model->mime_type)) {
                                        return Html::tag(
                                            'video',
                                            Html::tag('source', '', ['src' => $setsModel->url, 'type' => $model->mime_type]),
                                            ['width' => '40px', 'controls' => true]
                                        );
                                    } else {
                                        return Html::a(Yii::t('files', 'Preview'), $setsModel->url, ['tagret' => '_blank']);
                                    }
                                }
                            ],
                            [
                                'attribute' => 'data',
                                'format' => 'raw',
                                'value' => function ($model) {
                                    return Html::activeTextInput($model, "[{$model->id}]data", ['class' => 'form-control']);
                                }
                            ],
                            [
                                'class' => 'yii\grid\ActionColumn',
                                'template' => '{delete}',
                                'buttons' => [
                                    'delete' => function ($url, $model) {
                                        return Html::a('<span class="fa fa-times"></span>', ['/files/default/delete-sets', 'id' => $model->id], [
                                            'class' => 'delete-attachment-file',
                                        ]);
                                    }
                                ]
                            ],
                        ],
                    ]) ?>
                    <script>
                        jQuery('#sets-list').SortableGridView('<?= $setsSortableAction ?>');
                    </script>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <?= Html::button('Сохранить', ['class' => 'btn btn-primary file-edit-submit', 'data-url' => Url::to(['/files/default/ajax-update', 'id' => $model->id])]) ?>
        </div>
    </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->
