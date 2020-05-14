<?php

namespace mix8872\yiiFiles\models;

use mix8872\yiiFiles\behaviors\FileAttachBehavior;
use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\Url;
use yii\behaviors\TimestampBehavior;
use himiklab\sortablegrid\SortableGridBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "files".
 *
 * @property integer $id
 * @property integer $model_id
 * @property string $model_name
 * @property string $name
 * @property string $filename
 * @property string $mime_type
 * @property string $tag
 * @property integer $size
 * @property integer $order
 * @property integer $user_id
 * @property integer $created_at
 * @property mixed sets
 */
class File extends ActiveRecord
{
    private $module;
    private $webPath;
    private $webrootPath;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'file';
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if ($this->module = Yii::$app->getModule('files')) {
            $this->webPath = '@web' . $this->module->parameters['savePath'];
            $this->webrootPath = '@webroot' . $this->module->parameters['savePath'];
        } else {
            throw new InvalidConfigException('Module "files" is not defined');
        }

        Yii::$app->i18n->translations['files'] = [
            'class' => 'yii\i18n\PhpMessageSource',
            'sourceLanguage' => 'ru-RU',
            'basePath' => '@vendor/mix8872/yii2-files/src/messages',
        ];
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        parent::behaviors();
        return [
            [
                'class' => TimestampBehavior::class,
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['created_at'],
                ],
            ],
            'sort' => [
                'class' => SortableGridBehavior::class,
                'sortableAttribute' => 'order'
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributes()
    {
        return array_merge(parent::attributes(), [
            'url',
            'trueUrl',
            'sizes'
        ]);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['model_id', 'model_name', 'name', 'filename', 'mime_type', 'tag', 'size', 'user_id'], 'required'],
            [['model_id', 'size', 'order', 'user_id', 'created_at'], 'integer'],
            [['model_name', 'name', 'filename', 'mime_type', 'tag'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'model_id' => Yii::t('files', 'ID модели'),
            'model_name' => Yii::t('files', 'Имя модели'),
            'name' => Yii::t('files', 'Имя файла'),
            'filename' => Yii::t('files', 'Файл'),
            'mime_type' => 'Mime тип',
            'tag' => Yii::t('files', 'Тег'),
            'size' => Yii::t('files', 'Размер'),
            'order' => Yii::t('files', 'Порядок'),
            'user_id' => Yii::t('files', 'Пользователь'),
            'created_at' => Yii::t('files', 'Добавлен'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function delete()
    {
        $this->owner->trigger(FileAttachBehavior::EVENT_FILE_DELETE);

        $modelPath = Yii::getAlias($this->webrootPath . self::getModelName($this->model_name) . "/{$this->model_id}");
        $path = "$modelPath/{$this->tag}";
        if (file_exists("$path/{$this->filename}")) {
            unlink("$path/{$this->filename}");
        }

        if ($sizes = glob($path . '/' . preg_replace('/(\.[^\.]*$)/ui', "*\$1", $this->filename))) {
            foreach ($sizes as $size) {
                unlink($size);
            }
        }

        if ($sets = $this->sets) {
            foreach ($sets as $set) {
                $set->delete();
            }
        }

        if ($this->_is_empty_dir($path)) {
            rmdir($path);
        }

        if ($this->_is_empty_dir($modelPath)) {
            rmdir($modelPath);
        }

        parent::delete();
    }

    /**
     * Check if directory is empty
     * @param $dir - Path to directory
     * @return bool
     */
    protected function _is_empty_dir($dir)
    {
        return (is_dir($dir) && ($files = @scandir($dir)) && count($files) <= 2);
    }

    /**
     * Remove directory recursively
     * @param $path - Path to directory
     * @param string $t - Remove this directory
     * @return string
     */
    protected function _rRemoveDir($path, $t = '1')
    {
        $rtrn = '1';
        if (file_exists($path) && is_dir($path)) {
            $dirHandle = opendir($path);
            while (false !== ($file = readdir($dirHandle))) {
                if ($file != '.' && $file != '..') {
                    $tmpPath = $path . '/' . $file;
                    chmod($tmpPath, 0777);
                    if (is_dir($tmpPath)) {
                        fullRemove_ff($tmpPath);
                    } else {
                        if (file_exists($tmpPath)) {
                            unlink($tmpPath);
                        }
                    }
                }
            }
            closedir($dirHandle);
            if ($t == '1') {
                if (file_exists($path)) {
                    rmdir($path);
                }
            }
        } else {
            $rtrn = '0';
        }
        return $rtrn;
    }

    /**
     * @inheritdoc
     */
    public function afterFind()
    {
        $this->url = Yii::getAlias($this->webPath . self::getModelName($this->model_name) . "/{$this->model_id}/{$this->tag}/{$this->filename}");
        $this->trueUrl = Url::to([$this->url], true);
        $this->trueUrl = rtrim($this->trueUrl, '/');

        preg_match('/\/\w{2}\//ui', $this->trueUrl, $match);
        $match = trim(array_pop($match), '/');

        if ($langModule = Yii::$app->getModule('languages')) {
            if (array_search($match, $langModule->languages) !== false) {
                $this->trueUrl = preg_replace('/\/\w{2}\//ui', '/', $this->trueUrl);
            }
        }

        $sizes = $this->getSizes();
        if ($sizes) {
            $this->sizes = $sizes;
        }

        parent::afterFind();
    }

    /**
     * {@inheritDoc}
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        $this->owner->trigger($insert ? FileAttachBehavior::EVENT_FILE_ADD : FileAttachBehavior::EVENT_FILE_UPDATE);
    }

    /**
     * @param bool $withFullPath - if true then add to result absolute path to the file
     * @return array
     */
    public function getSizes($withFullPath = false)
    {
        $result = array();
        $basePath = self::getModelName($this->model_name) . "/{$this->model_id}/{$this->tag}/";
        $path = Yii::getAlias($this->webPath . $basePath);
        $truePath = Url::to([Yii::getAlias($path)], true);
        $exFilename = explode('.', $this->filename);
        $module = Yii::$app->getModule('files');

        preg_match('/\/\w{2}\//u', $truePath, $match);
        $match = trim(array_pop($match), '/');

        if ($langModule = Yii::$app->getModule('languages')) { // remove lang from truePath
            if (array_search($match, $langModule->languages) !== false) {
                $truePath = preg_replace('/\/\w{1,2}\//u', '/', $truePath);
            }
        }

        if ($withFullPath) {
            $fullPath = Yii::getAlias($this->webrootPath . $basePath);
        }

        $sizesNameBy = $module->parameters['sizesNameBy'];
        if (isset($module->parameters['sizes']) && !empty($module->parameters['sizes'])) {
            foreach ($module->parameters['sizes'] as $key => $size) {
                if ((!isset($size['model']) || empty($size['model']))
                    || self::checkSizeModel($size, $this->model_name)
                ) {
                    $width = isset($size['width']) ? $size['width'] : null;
                    $height = isset($size['height']) ? $size['height'] : null;

                    switch ($sizesNameBy) {
                        case 'key':
                            $fileName = $exFilename[0] . '-' . $key . '.' . $exFilename[1];
                            break;
                        case 'template':
                            $template = $module->parameters['sizesNameTemplate'];
                            $nameSize = $width . 'x' . $height;
                            $template = preg_replace('/%s/u', $nameSize, $template);
                            $template = preg_replace('/%k/u', $key, $template);
                            $fileName = $exFilename[0] . '-' . $template . '.' . $exFilename[1];
                            break;
                        case 'size':
                        default:
                            $fileName = $exFilename[0] . '-' . $width . 'x' . $height . '.' . $exFilename[1];
                    }

                    if ($width || $height) {
                        $result[$key] = [
                            'url' => $path . $fileName,
                            'trueUrl' => $truePath . $fileName,
                            'width' => $width,
                            'height' => $height,
                        ];
                        if ($withFullPath) {
                            $result[$key]['path'] = $fullPath . $fileName;
                        }
                    }
                }
            }
        }
        return $result;
    }

    /**
     * Check for existing current model in sizes array
     * @param $size
     * @param $modelName
     * @return bool
     */
    public static function checkSizeModel($size, $modelName)
    {
        return (!empty($size['model'])
            && (
                (is_array($size['model']) && in_array($modelName, $size['model'], true))
                || (is_string($size['model']) && $modelName === $size['model'])
            )
        );
    }

    /**
     * @param null $language
     * @return array|null|ActiveRecord
     */
    public function getLangContent($language = null)
    {
        $language = $language ?: Yii::$app->language;
        $language = preg_replace('/-\w+$/', '', $language);
        return $this->hasMany(FileContent::class, ['file_id' => 'id'])->where(['lang' => $language])->one();
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getContent()
    {
        $language = preg_replace('/-\w+$/', '', Yii::$app->language);
        return $this->hasOne(FileContent::class, ['file_id' => 'id'])->andWhere(['lang' => $language]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSets()
    {
        return $this->hasMany(FileSet::class, ['file_id' => 'id'])->indexBy('id')->orderBy('order ASC');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOwner()
    {
        return $this->hasOne($this->model_name, ['id' => 'model_id']);
    }

    /**
     * @param $modelClass
     * @return mixed|string
     */
    public static function getModelName($modelClass)
    {
        $classExplode = explode('\\', $modelClass);
        return array_pop($classExplode);
    }
}
