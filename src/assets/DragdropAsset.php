<?php
/**
 * Created by PhpStorm.
 * User: Mix
 * Date: 30.10.2017
 * Time: 10:32
 */

namespace mix8872\yiiFiles\assets;


class DragdropAsset extends \yii\web\AssetBundle
{
    public $sourcePath = '@vendor/mix8872/yii2-files/src/assets';
    public $css = [
        'dropify/dropify-multiple.min.css',
    ];
    public $js = [
        'dropify/dropify-multiple.js'
    ];

    public $depends = [
		'mix8872\yiiFiles\assets\FilesAsset',
    ];
}
