<?php
/**
 * Created by PhpStorm.
 * User: Mix
 * Date: 30.10.2017
 * Time: 10:32
 */

namespace mix8872\yiiFiles\assets;


class FileinputAsset extends \yii\web\AssetBundle
{
    public $sourcePath = '@vendor/npm-asset/bootstrap-fileinput';
    public $css = [
        'css/fileinput.min.css',
    ];
    public $js = [
        'js/plugins/sortable.min.js',
        'js/fileinput.min.js',
        'js/locales/ru.js',
        'themes/explorer-fa/theme.js',
        'themes/fa/theme.js',
//        'bs-fileinput/js/plugins/popper.min.js',
    ];

    public $depends = [
		'mix8872\yiiFiles\assets\FilesAsset',
    ];
}
