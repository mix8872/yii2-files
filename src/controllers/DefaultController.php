<?php

namespace mix8872\yiiFiles\controllers;

use mix8872\yiiFiles\models\FileContent;
use himiklab\sortablegrid\SortableGridAction;
use mix8872\yiiFiles\models\FileSet;
use mix8872\yiiFiles\widgets\FilesWidget;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\data\ArrayDataProvider;
use yii\filters\VerbFilter;
use mix8872\yiiFiles\models\File;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\UploadedFile;

/**
 * MenuController implements the CRUD actions for Menu model.
 */
class DefaultController extends \yii\web\Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'ajax-update' => ['POST'],
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * @return bool|string
     */
    public function getViewPath()
    {
        return Yii::getAlias('@vendor/mix8872/yii2-files/src/widgets/views');
    }

    /**
     * @return array
     */
    public function actions()
    {
        return [
            'sort' => [
                'class' => SortableGridAction::class,
                'modelName' => 'mix8872\yiiFiles\models\File',
            ],
            'sort-sets' => [
                'class' => SortableGridAction::class,
                'modelName' => 'mix8872\yiiFiles\models\FileSet',
            ],
        ];
    }

    /**
     * Lists all Menu models.
     * @return mixed
     */
    public function actionIndex()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => File::find(),
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionUpdate($id)
    {
        $inputFileTypes = ''; //file type for input
        $jsAllowedFileTypes = []; //allowed file types for js
        $jsAllowedFileExtensions = []; //allowed file extensions for js

        $model = $this->findModel($id);
        $setModel = new FileSet();
        if ($langModule = Yii::$app->getModule('languages')) {
            $languages = $langModule->languages;
        } else {
            $languages = [Yii::$app->language => Yii::$app->language];
        }

        $setsDataProvider = new ArrayDataProvider([
            'allModels' => $model->sets
        ]);

        $type = explode('/', $model->mime_type);
        $this->_setFiletypes("$type[0]/*", $inputFileTypes, $jsAllowedFileTypes, $jsAllowedFileExtensions);

        return $this->renderAjax('update', compact('model', 'setModel', 'languages', 'setsDataProvider', 'inputFileTypes', 'jsAllowedFileTypes', 'jsAllowedFileExtensions'));
    }

    /**
     * @param $id
     * @return bool
     */
    public function actionAjaxUpdate($id)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        if (Yii::$app->request->isAjax) {
            $content = FileContent::find()->where(['file_id' => (int)$id])->indexBy('id')->all();
            $sets = FileSet::find()->where(['file_id' => (int)$id])->indexBy('id')->all();
            $post = Yii::$app->request->post();
            if (Model::loadMultiple($content, $post) && Model::loadMultiple($sets, $post)) {
                $result = true;
                foreach ($content as $item) {
                    if (!$item->save()) {
                        error_log(print_r($item->getErrorSummary(1), 1));
                        $result = false;
                    }
                }
                foreach ($sets as $item) {
                    if (!$item->save()) {
                        error_log(print_r($item->getErrorSummary(1), 1));
                        $result = false;
                    }
                }
                return $result;
            } else {
                return false;
            }
        }
        return false;
    }

    public function actionSetsUpload($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        if ($attachments = UploadedFile::getInstances(new FileSet(), 'attachment')) {

            foreach ($attachments as $file) {
                $set = new FileSet([
                    'file_id' => (int)$id,
                    'attachment' => $file
                ]);
                $set->save();
            }
        }
        return new class() {
        };
    }

    /**
     * Deletes an existing File model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function actionDelete($id)
    {
        return $this->findModel($id)->delete();
    }

    /**
     * @param $id
     * @return bool
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function actionDeleteSets($id)
    {
        if ($set = FileSet::findOne((int)$id)) {
            return $set->delete();
        }
        return false;
    }

//------------------------------------------------------------------

    /**
     * Finds the File model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return File|null the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = File::find()->where(['id' => (int)$id])->with('content')->with('sets')->one()) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('Файл в базе не найден');
        }
    }

    /**
     * @param $types
     * @param $fileTypes
     * @param $allowedFileTypes
     * @param $allowedFileExtensions
     */
    protected function _setFiletypes($types, &$fileTypes, &$allowedFileTypes, &$allowedFileExtensions)
    {
        $type = explode('/', $types);
        $allowedFileTypes = FilesWidget::getFType($type[0]);
        isset($type[1]) && $type[1] === '*' ?: $allowedFileExtensions[] = FilesWidget::getExtensionByMime($types);
        $fileTypes = $types;
    }
}
