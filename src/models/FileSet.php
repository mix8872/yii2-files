<?php

namespace mix8872\yiiFiles\models;

use himiklab\sortablegrid\SortableGridBehavior;
use mix8872\yiiFiles\helpers\Translit;
use mix8872\yiiFiles\Module;
use Yii;
use yii\base\InvalidConfigException;
use yii\base\Security;
use yii\helpers\Url;

/**
 * This is the model class for table "file_set".
 *
 * @property int $id
 * @property int $file_id
 * @property int $order
 * @property string $filename
 * @property string $data
 *
 * @property File $file
 */
class FileSet extends \yii\db\ActiveRecord
{
    public $attachment;
    public $url;
    public $trueUrl;
    protected $module;
    protected $webPath;
    protected $webrootPath;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'file_set';
    }

    public function init()
    {
        parent::init();

        if ($this->module = Yii::$app->getModule('files')) {
            $this->webPath = '@web' . $this->module->parameters['savePath'];
            $this->webrootPath = '@webroot' . $this->module->parameters['savePath'];
        } else {
            throw new InvalidConfigException('Module "files" is not defined');
        }
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        parent::behaviors();
        return [
            'sort' => [
                'class' => SortableGridBehavior::class,
                'sortableAttribute' => 'order'
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['file_id', 'order'], 'integer'],
            [['order'], 'default', 'value' => 0],
            [['filename', 'data'], 'string', 'max' => 255],
            [['file_id'], 'exist', 'skipOnError' => true, 'targetClass' => File::class, 'targetAttribute' => ['file_id' => 'id']],
            [['attachment'], 'file', 'skipOnError' => true]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'file_id' => 'File ID',
            'order' => 'Порядок',
            'filename' => 'Файл',
            'data' => 'Data',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFile()
    {
        return $this->hasOne(File::class, ['id' => 'file_id']);
    }

    public function delete()
    {
        $file = $this->file;
        $path = Yii::getAlias($this->webrootPath . File::getModelName($file->model_name) . "/{$file->model_id}/{$file->tag}/{$file->id}-set");
        if (file_exists("$path/{$this->filename}")) {
            unlink("$path/{$this->filename}");
            if ($this->_is_empty_dir($path)) {
                rmdir($path);
            }
        }

        return parent::delete();
    }

    public function beforeSave($insert)
    {
        if ($insert && $this->attachment) {
            $file = File::findOne($this->file_id);
            $path = Yii::getAlias($this->webrootPath . File::getModelName($file->model_name) . "/{$file->model_id}/{$file->tag}/{$file->id}-set");
            if (!is_dir($path) && !mkdir($concurrentDirectory = $path, 0755, true) && !is_dir($concurrentDirectory)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
            }
            if ($this->module->getFileNameBy($file->model_name) === Module::NAME_BY_TRANSLIT) {
                $filename = Translit::t($this->attachment->name);
                preg_match('/(.*)\.[^.]+$/', $filename, $fFileName);
                $filename = $baseFileName = $fFileName[1] ?? $fFileName[0];
                $i = 1;
                while (is_file("$path/$filename.{$this->attachment->extension}")) {
                    $filename = $baseFileName . $i;
                    $i++;
                }
            } else {
                $security = new Security();
                $filename = $security->generateRandomString(16);
                while (is_file("$path/$filename.{$this->attachment->extension}")) {
                    $filename = Security::generateRandomString(16);
                }
            }
            if ($this->attachment->saveAs("$path/$filename.{$this->attachment->extension}")) {
                $this->filename = "$filename.{$this->attachment->extension}";
                return parent::beforeSave($insert);
            } else {
                $this->addError('attachment', 'Ошибка сохранения файла');
                return false;
            }
        }
        return parent::beforeSave($insert);
    }

    public function afterFind()
    {
        $file = $this->file;
        $this->url = Yii::getAlias($this->webPath . File::getModelName($file->model_name) . "/{$file->model_id}/{$file->tag}/{$file->id}-set/$this->filename");
        $this->trueUrl = Url::to([$this->url], true);
        $this->trueUrl = rtrim($this->trueUrl, '/');

        parent::afterFind();
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
}
