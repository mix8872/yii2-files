<?php

namespace mix8872\yiiFiles;

use mix8872\yiiFiles\helpers\Translit;
use yii\base\Security;
use yii\filters\AccessControl;
use yii\base\InvalidConfigException;

/**
 * File module.
 */
class Module extends \yii\base\Module implements \yii\base\BootstrapInterface
{
    public $parameters;
    public $attributes;

    public const DRIVER_GD = 'gd';
    public const DRIVER_IMAGICK = 'imagick';

    // defaults
    public const SAVE_PATH = '/uploads/attachments/';
    public const FILES_NAME_BY = 'random';
    public const IMG_PROCESS_DRIVER_DEFAULT = self::DRIVER_GD;
    public const SIZES_NAME_BY = 'size';
    public const SIZES_NAME_TEMPLATE = '%s';

    public const NAME_BY_TRANSLIT = 'translit';
    public const NAME_BY_RAND = 'rand';

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        $this->controllerNamespace = 'mix8872\yiiFiles\controllers';
        $this->setViewPath('@vendor/mix8872/yii2-files/src/views');
        $this->registerTranslations();

        if (!isset($this->parameters['savePath'])) {
            $this->parameters['savePath'] = self::SAVE_PATH;
        } else {
            if (is_string($this->parameters['savePath'])) {
                if (substr($this->parameters['savePath'], -1) != '/') {
                    $this->parameters['savePath'] = $this->parameters['savePath'] . '/';
                }
                if ($this->parameters['savePath'][0] != '/') {
                    $this->parameters['savePath'] = '/' . $this->parameters['savePath'];
                }
            } else {
                return new InvalidConfigException('Parameter "savePath" must be a string!');
            }
        }

        if (!isset($this->parameters['filesNameBy'])) {
            $this->parameters['filesNameBy'] = self::FILES_NAME_BY;
        } else {
            if (!is_string($this->parameters['filesNameBy'])) {
                return new InvalidConfigException('Parameter "filesNameBy" must be a string!');
            }
        }

        if (!isset($this->parameters['imgProcessDriver'])) {
            $this->parameters['imgProcessDriver'] = self::IMG_PROCESS_DRIVER_DEFAULT;
        } else {
            if (!is_string($this->parameters['imgProcessDriver'])) {
                return new InvalidConfigException('Parameter "imgProcessDriver" must be a string!');
            }
        }

        if (!isset($this->parameters['sizesNameBy'])) {
            $this->parameters['sizesNameBy'] = self::SIZES_NAME_BY;
        } else {
            if (!is_string($this->parameters['sizesNameBy'])) {
                return new InvalidConfigException('Parameter "sizesNameBy" must be a string!');
            }
        }

        if (!isset($this->parameters['sizesNameTemplate'])) {
            $this->parameters['sizesNameTemplate'] = self::SIZES_NAME_TEMPLATE;
        } else {
            if (!is_string($this->parameters['sizesNameTemplate'])) {
                return new InvalidConfigException('Parameter "sizesNameTemplate" must be a string!');
            }
        }

        if ($this->parameters['sizesNameBy'] === 'template'
            && preg_match('/(%k|%s)/u', $this->parameters['sizesNameTemplate']) === false
        ) {
            return new InvalidConfigException('Parameter "sizesNameBy" set to "template", but template does not meet the requirements! Template must contain at least one of the characters "%k" or/and "%s".');
        }
    }

    public function getFileNameBy($modelClass)
    {
        $fileNameBy = $this->parameters['filesNameBy'];
        if ($fileNameBy === self::NAME_BY_TRANSLIT
            || ((is_array($fileNameBy)
                    && isset($fileNameBy[0])
                    && $fileNameBy[0] === self::NAME_BY_TRANSLIT
                )
                && (!isset($fileNameBy['model'])
                    || ((is_array($fileNameBy['model']) && in_array($modelClass, $fileNameBy['model'], true))
                        || (is_string($fileNameBy['model']) && $modelClass === $fileNameBy['model'])
                    )
                )
            )
        ) {
            return self::NAME_BY_TRANSLIT;
        } else {
            return self::NAME_BY_RAND;
        }
    }

    public function bootstrap($app)
    {
        if ($app instanceof \yii\console\Application) {
            $this->controllerNamespace = 'mix8872\yiiFiles\commands';
        }
    }

    /**
     * Register translation for module
     */
    public function registerTranslations()
    {
        \Yii::$app->i18n->translations['files'] = [
            'class' => 'yii\i18n\PhpMessageSource',
            'sourceLanguage' => 'ru-RU',
            'basePath' => '@vendor/mix8872/yii2-files/src/messages',
        ];

    }
}
