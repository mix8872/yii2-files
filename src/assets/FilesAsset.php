<?php
/**
 * Created by PhpStorm.
 * User: Mix
 * Date: 30.10.2017
 * Time: 10:32
 */

namespace mix8872\yiiFiles\assets;


class FilesAsset extends \yii\web\AssetBundle
{
    public $sourcePath = '@vendor/mix8872/yii2-files/src/assets';
    public $css = [
        'css/magnific-popup.css',
        'css/files.css',
    ];
    public $js = [
        'js/jquery.magnific-popup.min.js',
        'js/files.js',
    ];

    public $depends = [
		'yii\jui\JuiAsset',
		'backend\assets\AppAsset',
    ];
}
