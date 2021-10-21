<?php
namespace mix8872\yiiFiles\commands;

use Intervention\Image\ImageManager;
use yii\base\InvalidConfigException;
use yii\console\Controller;
use yii\helpers\Console;
use mix8872\yiiFiles\models\File;
use Yii;

class ManageController extends Controller
{
    protected $manager;
    protected $driver;
    public $overwrite;

    public function options($actionId)
    {
        return array_merge(parent::options($actionId), [
            'overwrite'
        ]);
    }

    public function init()
    {
        if (!($module = Yii::$app->getModule('files'))) {
            throw new InvalidConfigException('Module "files" is not defined');
        }
        $this->driver = $module->parameters['imgProcessDriver'];
        $this->manager = new ImageManager(['driver' => $this->driver]);
    }

    public function actionRebuild($className = null)
    {
        $filter = $className ? ['model_name' => trim($className, '\\')] : null;
        $class = trim($className, '\\');
        $models = File::find()->where(['model_name' => $className])->all();

        if ($models = File::findAll($filter)) {
            foreach ($models as $model) {
                if (!preg_match("/^image\/((?!svg|gif)).+$/i", $model->mime_type)) {
                    echo "File {$mode->filePath} is not a image";
                    continue;
                }
                echo "Process file {$model->model_name}:{$model->model_id}:{$model->filePath}" . PHP_EOL;
                if (!is_file($model->filePath)) {
                    echo 'File not found';
                    continue;
                }
                foreach ($model->getSizes(true) as $name => $size) {
                    echo " -- make size {$name} to {$size['path']}" . PHP_EOL;
                    if (is_file($size['path'])) {
                        if (!$this->overwrite) {
                            echo "  -- file already exists" . PHP_EOL;
                            continue;
                        }
                        unlink($size['path']);
                    }
                    $this->_saveSize($size['width'], $size['height'], $model->filePath, $size['path']);
                }
            }
        }
    }

    protected function _saveSize($width, $height, $filePath, $path)
    {
        if ($width || $height) {
            return $this->manager->make($filePath)->resize($width, $height, static function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            })->save($path);
        }
    }
}
