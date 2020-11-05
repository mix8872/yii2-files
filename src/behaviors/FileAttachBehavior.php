<?php

namespace mix8872\yiiFiles\behaviors;

use Intervention\Image\ImageManager;
use mix8872\yiiFiles\classes\FilesCollection;
use mix8872\yiiFiles\models\FileContent;
use mix8872\yiiFiles\Module;
use Yii;
use mix8872\yiiFiles\models\File;
use yii\base\BaseObject;
use yii\db\ActiveRecord;
use yii\base\InvalidConfigException;
use yii\web\UploadedFile;
use yii\base\Security;
use mix8872\yiiFiles\helpers\Translit;

class FileAttachBehavior extends \yii\base\Behavior
{
    public const EVENT_FILE_ADD = 'file_add';
    public const EVENT_FILE_UPDATE = 'file_update';
    public const EVENT_FILE_DELETE = 'file_delete';

    public $attributes;
    public $indexBy = 'id';
    protected $modelClass;
    protected $path;
    protected $filePath;
    protected $module;
    protected $manager;
    protected $modelId;
    protected $driver;

    protected static $types = [
        'txt' => 'text/plain',
        'htm' => 'text/html',
        'html' => 'text/html',
        'php' => 'text/html',
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'xml' => 'application/xml',
        'swf' => 'application/x-shockwave-flash',
        'flv' => 'video/x-flv',

        // images
        'png' => 'image/png',
        'jpe' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'gif' => 'image/gif',
        'bmp' => 'image/bmp',
        'ico' => 'image/vnd.microsoft.icon',
        'tiff' => 'image/tiff',
        'tif' => 'image/tiff',
        'svg' => 'image/svg+xml',
        'svgz' => 'image/svg+xml',

        // archives
        'zip' => 'application/zip',
        'rar' => 'application/x-rar-compressed',
        'exe' => 'application/x-msdownload',
        'msi' => 'application/x-msdownload',
        'cab' => 'application/vnd.ms-cab-compressed',

        // audio/video
        'mp3' => 'audio/mpeg',
        'mp4' => 'video/mp4',
        'avi' => 'video/mp4',
        'qt' => 'video/quicktime',
        'mov' => 'video/quicktime',

        // adobe
        'pdf' => 'application/pdf',
        'psd' => 'image/vnd.adobe.photoshop',
        'ai' => 'application/postscript',
        'eps' => 'application/postscript',
        'ps' => 'application/postscript',

        // ms office
        'doc' => 'application/msword',
        'rtf' => 'application/rtf',
        'xls' => 'application/vnd.ms-excel',
        'ppt' => 'application/vnd.ms-powerpoint',

        // open office
        'odt' => 'application/vnd.oasis.opendocument.text',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
    ];

    protected static $gdSupported = [
        'image/png',
        'image/jpeg',
        'image/gif',
    ];

    protected static $imagickSupported = [
        'image/png',
        'image/jpeg',
        'image/gif',
        'image/bmp',
        'image/vnd.microsoft.icon',
        'image/tiff',
    ];

    /**
     * @inheritdoc
     */
    public function __construct()
    {
        BaseObject::__construct();
        if (!($this->module = Yii::$app->getModule('files'))) {
            throw new InvalidConfigException('Module "files" is not defined');
        }
        $this->driver = $this->module->parameters['imgProcessDriver'];
        $this->manager = new ImageManager(['driver' => $this->driver]);
    }

    /**
     * @inheritdoc
     */
    public function canGetProperty($name, $checkVars = true)
    {
        return method_exists($this, 'get' . $name)
            || ($checkVars && property_exists($this, $name))
            || isset($this->attributes[$name])
            || in_array($name, $this->attributes, true);
    }

    /**
     * @inheritdoc
     */
    public function __get($name)
    {
        if (isset($this->attributes[$name]) || in_array($name, $this->attributes, true)) {
            $multiple = $this->attributes[$name]['multiple'] ?? $this->module->attributes[$name]['multiple'] ?? false;
            if ($multiple) {
                return new FilesCollection($this->getFiles($name));
            } else {
                return $this->getFiles($name, true);
            }
        }
        return parent::__get($name);
    }

    //TODO: добавить метод __set()

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_VALIDATE => 'setParams',
            ActiveRecord::EVENT_BEFORE_DELETE => 'setParams',
            ActiveRecord::EVENT_BEFORE_DELETE => 'deleteAllAttachments',
            ActiveRecord::EVENT_AFTER_INSERT => 'saveAttachments',
            ActiveRecord::EVENT_AFTER_UPDATE => 'saveAttachments',
            ActiveRecord::EVENT_AFTER_FIND => 'setParams',
            self::EVENT_FILE_ADD => 'onFileAdd',
            self::EVENT_FILE_UPDATE => 'onFileUpdate',
            self::EVENT_FILE_DELETE => 'onFileDelete'
        ];
    }

    /**
     * @param $event
     * @return bool
     * @throws \Throwable
     * @throws \yii\base\Exception
     * @throws \yii\db\StaleObjectException
     */
    public function saveAttachments($event)
    {
        $this->setParams();
        foreach ($this->attributes as $key => $attribute) {
            if (is_array($attribute)) {
                $tagAttributes = $attribute;
                $attribute = $key;
            } else {
                $tagAttributes = $this->module->attributes[$attribute] ?? [];
            }

            $attachments = UploadedFile::getInstances($this->owner, $attribute);
            if (!$attachments) {
                $index = $this->owner->{$this->indexBy};
                $attachments = UploadedFile::getInstances($this->owner, "[$index]$attribute");
            }
            if ($attachments) {
                if (!isset($tagAttributes['multiple']) || !$tagAttributes['multiple']) {
                    if ($olds = $this->getFiles($attribute)) {
                        foreach ($olds as $old) {
                            $old->delete();
                        }
                    }
                }
                $this->_setPath($attribute);
                foreach ($attachments as $file) {
                    $this->_saveFile($file, $tagAttributes, $attribute);
                }
            }
        }
        return false;
    }

    /**
     * @param $attribute
     * @param $url
     * @return bool
     * @throws \Throwable
     * @throws \yii\base\Exception
     * @throws \yii\db\StaleObjectException
     */
    public function attachByUrl($attribute, $url)
    {
        $tagAttributes = [];
        $found = false;
        foreach ($this->attributes as $key => $item) {
            if (is_array($item)) {
                $tagAttributes = $attribute;
                $item = $key;
            } else {
                $tagAttributes = $this->module->attributes[$attribute] ?? [];
            }
            if ($item === $attribute) {
                $found = true;
                break;
            }
        }

        if (!$found) {
            return false;
        }

        if (!isset($tagAttributes['multiple']) || !$tagAttributes['multiple']) {
            if ($olds = $this->getFiles($attribute)) {
                foreach ($olds as $old) {
                    $old->delete();
                }
            }
        }

        $file = new class() {
            public $baseName;
            public $type;
            public $extension;
            public $size;
            public $tempName;

            public function saveAs($path)
            {
                try {
                    $res = copy($this->tempName, $path);
                    return $res;
                } catch (\Error $e) {
                    error_log($e->getMessage());
                    return false;
                }
            }
        };

        if (strstr($url, 'http') === false && filetype($url) === 'file') {
            $data = pathinfo($url);
            $file->baseName = $data['filename'] . '-' . date("d-m-Y-H-i");
            $file->type = mime_content_type($url);
            $file->extension = $data['extension'];
            $file->size = filesize($url);
        } else {
            $data = @get_headers($url, true);
            $path = parse_url($url, PHP_URL_PATH);
            $file->baseName = $path ? basename($path) : (isset($data['ETag']) ? trim($data['ETag'], '"') : (new Security())->generateRandomString(12));
            $file->extension = substr(strstr($file->type, '/'), 1, strlen($file->type));
            $file->type = $data['Content-Type'] ?? self::$types[$file->extension];
            $file->size = $data['Content-Length'] ?? 0;
        }
        $file->tempName = $url;

        $this->_setPath($attribute);
        return $this->_saveFile($file, $tagAttributes, $attribute);
    }

    /**
     * Set modelClass and modelId
     */
    public function setParams()
    {
        $this->modelClass = get_class($this->owner);
        $this->modelId = $this->owner->id;
    }

    /**
     * Set path, create directory if not exist
     * @param $tag
     */
    protected function _setPath($tag)
    {
        $this->path = Yii::getAlias('@webroot' . $this->module->parameters['savePath'] . $this->owner->formName() . '/' . $this->modelId . "/" . $tag);
        if (!is_dir($this->path) && !mkdir($concurrentDirectory = $this->path, 0755, true) && !is_dir($concurrentDirectory)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
        }
    }

    /**
     * @param $file
     * @param $tagAttributes
     * @param $tag
     * @return bool
     * @throws \yii\base\Exception
     */
    protected function _saveFile($file, $tagAttributes, $tag)
    {
        $allow = true;
        if ($tagAttributes && isset($tagAttributes['filetypes'])) { //if isset filetypes in behavior check uploaded file
            $allow = $this->_checkFileType($tagAttributes['filetypes'], $file);
        }
        if ($allow) {
            $filename = $this->_getFileName($file->baseName, $this->path, $file->extension);
            $this->filePath = $this->path . "/" . $filename . "." . $file->extension;

            if (preg_match("/^image\/((?!svg|gif)).+$/i", $file->type) && $this->_checkSupportedImages($file)) {
                try {
                    $imgSaveRes = $this->manager->make($file->tempName)->orientate()->save($this->filePath);
                } catch (\Exception $e) {
                    error_log($e->getMessage());
                    return false;
                }
                if (!$imgSaveRes) {
                    return false;
                }

                if (isset($tagAttributes['resize'])) {
                    $resize = $tagAttributes['resize'];
                    if ($this->_checkResizeArray($resize)) {
                        $resWidth = $resize['width'] ?? null;
                        $resHeight = $resize['height'] ?? null;
                        $this->_saveSize($resWidth, $resHeight, $this->filePath);
                    }
                }
                return $this->_saveFileModel($file, $filename, $tag, true);
            } elseif ($file->saveAs($this->filePath)) {
                return $this->_saveFileModel($file, $filename, $tag);
            } else {
                error_log("FILE SAVE ERROR: " . $file->baseName);
            }
        } else {
            error_log("FILE VALIDATION ERROR: " . $file->baseName);
        }
        return false;
    }

    /**
     * @param $file
     * @param $filename
     * @param $tag
     * @param bool $isImage
     * @return bool
     */
    protected function _saveFileModel($file, $filename, $tag, $isImage = false)
    {
        $model = new File();
        $model->model_id = $this->modelId;
        $model->model_name = $this->modelClass;
        $model->name = $file->baseName;
        $model->filename = "$filename.{$file->extension}";
        $model->mime_type = $file->type;
        $model->tag = $tag;
        $model->size = $file->size;
        $model->user_id = Yii::$app->user->getId();

        if ($isImage) {
            foreach ($model->getSizes(true) as $size) {
                $this->_saveSize($size['width'], $size['height'], $size['path']);
            }
        }

        if ($result = $model->save()) {
            if ($langModule = \Yii::$app->getModule('languages')) {
                foreach ($langModule->languages as $lang) {
                    $this->_addContentModel($model, $lang);
                }
            } else {
                $lang = \Yii::$app->language;
                $this->_addContentModel($model, $lang);
            }
            return $result;
        } else {
            $errors = $model->getErrors();
            error_log('FILE SAVE IN DB ERROR: ' . print_r($errors, 1));
        }
        return false;
    }

    /**
     * @param $model
     * @param $lang
     */
    protected function _addContentModel($model, $lang)
    {
        $lang = strtolower(preg_replace('/(\w{2})-(\w{2})/u', '$1', $lang));
        $fileContent = new FileContent();
        $fileContent->file_id = $model->id;
        $fileContent->lang = $lang;
        $fileContent->save();
    }

    /**
     * @param $resize
     * @return bool
     */
    protected function _checkResizeArray($resize)
    {
        return isset($resize)
            && is_array($resize)
            && (isset($resize['width'])
                || isset($resize['height'])
            );
    }

    /**
     * @param $width - Resize width
     * @param $height - Resize height
     * @param $path - Save full path
     * @return \Intervention\Image\Image
     */
    protected function _saveSize($width, $height, $path)
    {
        if ($width || $height) {
            return $this->manager->make($this->filePath)->resize($width, $height, static function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            })->save($path);
        }
    }

    /**
     * @param $name
     * @param $path
     * @param $extension
     * @return mixed|null|string|string[]
     * @throws \yii\base\Exception
     */
    protected function _getFileName($name, $path, $extension)
    {
        if ($this->module->getFileNameBy($this->modelClass) === Module::NAME_BY_TRANSLIT) {
            $filename = $baseFileName = Translit::t($name);
            $i = 1;
            while (is_file("$path/$filename.$extension")) {
                $filename = $baseFileName . $i;
                $i++;
            }
        } else {
            $security = new Security();
            $filename = $security->generateRandomString(16);
            while (is_file($path . '/' . $filename . "." . $extension)) {
                $filename = Security::generateRandomString(16);
            }
        }

        return $filename;
    }

    /**
     * Delete all attachments of current model
     */
    public function deleteAllAttachments()
    {
        $files = File::find()->where(['model_id' => $this->owner->id, 'model_name' => $this->modelClass])->all();
        foreach ($files as $file) {
            $file->delete();
        }
    }

    /**
     * Relation
     * @return array|\yii\db\ActiveQuery
     */
    public function getModelFiles()
    {
        return $this->owner->hasMany(File::class, ['model_id' => 'id'])
            ->andWhere(['model_name' => $this->modelClass])
            ->with('content')
            ->with('sets')
            ->orderBy('order ASC');
    }

    /**
     * @param $tag
     * @param bool $single
     * @param bool $asQuery
     * @return array|\yii\db\ActiveQuery|ActiveRecord[]
     */
    public function getFiles($tag, $single = false, $asQuery = false)
    {
        if ($asQuery) {
            return $this->getModelFiles()->andWhere(['tag' => $tag]);
        }
        if ($single) {
            return $this->getModelFiles()->andWhere(['tag' => $tag])->one();
        }
        return $this->getModelFiles()->andWhere(['tag' => $tag])->all();
    }

    /**
     * @param bool $asQuery
     * @return array|\yii\db\ActiveQuery|ActiveRecord[]
     */
    public function getAllFiles($asQuery = false)
    {
        if ($asQuery) {
            return $this->getModelFiles();
        }
        return $this->getModelFiles()->all();
    }

    /**
     * @param array $allowed
     * @param \yii\web\UploadedFile $file
     * @return boolean
     */
    protected function _checkFileType($allowed, $file)
    {
        if (is_array($allowed)) {
            foreach ($allowed as $item) {
                $item = preg_replace('%/\*$%u', '/.*', $item);
                if (preg_match('%' . $item . '%ui', $file->type)) {
                    return true;
                }
            }
        } else {
            if (preg_match('%' . preg_replace('%/\*$%u', '/.*', $allowed) . '%ui', $file->type)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param $file
     * @return bool
     */
    protected function _checkSupportedImages($file)
    {
        switch (true) {
            case $this->driver === Module::DRIVER_GD && !in_array($file->type, self::$gdSupported, true):
                error_log('Current image format is not supported by GD image driver');
                return false;
            case $this->driver === Module::DRIVER_IMAGICK && !in_array($file->type, self::$imagickSupported, true):
                error_log('Current image format is not supported by Imagick image driver');
                return false;
            default:
                return true;
        }
    }

    public function onFileAdd()
    {
        if (method_exists($this->owner, 'onFileAdd')) {
            $this->owner->onFileAdd();
        }
    }

    public function onFileUpdate()
    {
        if (method_exists($this->owner, 'onFileUpdate')) {
            $this->owner->onFileUpdate();
        }
    }

    public function onFileDelete()
    {
        if (method_exists($this->owner, 'onFileDelete')) {
            $this->owner->onFileDelete();
        }
    }
}
