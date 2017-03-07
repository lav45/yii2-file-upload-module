<?php

namespace lav45\fileUpload\actions;

use Yii;
use yii\base\Action;
use yii\base\DynamicModel;
use yii\base\InvalidCallException;
use yii\base\InvalidConfigException;
use yii\helpers\FileHelper;
use yii\web\BadRequestHttpException;
use yii\web\Response;
use yii\web\UploadedFile;

/**
 * Class UploadAction
 * @package vova07\imperavi\actions
 *
 * UploadAction for images and files.
 *
 * Usage:
 *
 * ```php
 * public function actions()
 * {
 *     return [
 *         'upload-image' => [
 *             'class' => 'frontend\components\actions\UploadAction',
 *             'url' => '/statics',
 *             'path' => '@webroot/statics',
 *             'validatorOptions' => [
 *                 'maxWidth' => 1000,
 *                 'maxHeight' => 1000
 *             ]
 *         ],
 *         'file-upload' => [
 *             'class' => 'frontend\components\actions\UploadAction',
 *             'url' => '/statics',
 *             'path' => '@webroot/statics',
 *             'uploadOnlyImage' => false,
 *             'validatorOptions' => [
 *                 'maxSize' => 40000
 *             ]
 *         ]
 *     ];
 * }
 * ```
 */
class UploadAction extends Action
{
    /**
     * @var string Variable's name that Imperavi Redactor sent upon image/file upload.
     */
    public $uploadParam = 'file';
    /**
     * @var array Model validator options
     */
    public $validatorOptions = [];
    /**
     * @var array|\Closure
     */
    public $afterRun;
    /**
     * @var string Path to directory where files will be uploaded
     */
    private $path;
    /**
     * @var string URL path to directory where files will be uploaded
     */
    private $url;
    /**
     * @var string Model validator name
     */
    private $validator = 'image';

    public function getPath()
    {
        return $this->path;
    }

    public function setPath($path)
    {
        if (empty($path)) {
            throw new InvalidConfigException('The "path" attribute must be set.');
        }

        $path = Yii::getAlias($path);
        $path = rtrim($path, DIRECTORY_SEPARATOR);

        if (!FileHelper::createDirectory($path)) {
            throw new InvalidCallException("Directory specified in 'path' attribute doesn't exist or cannot be created.");
        }

        $this->path = $path;
    }

    public function setUrl($str)
    {
        if (empty($str)) {
            throw new InvalidConfigException('The "url" attribute must be set.');
        }

        $str = Yii::getAlias($str);
        $this->url = rtrim($str, '/');
    }

    public function setUploadOnlyImage($flag)
    {
        $this->validator = $flag === true ? 'image' : 'file';
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        $response = Yii::$app->getResponse();
        $response->format = Response::FORMAT_JSON;

        if (Yii::$app->getRequest()->getIsPost() === false) {
            throw new BadRequestHttpException('Only POST is allowed');
        }

        $file = UploadedFile::getInstanceByName($this->uploadParam);

        $model = new DynamicModel(['file' => $file]);
        $model->addRule('file', $this->validator, $this->validatorOptions);

        if ($model->validate() === false) {
            $result = ['error' => $model->getFirstError('file')];
        } else {
            $response->getHeaders()->set('Vary', 'Accept');

            $fileExtension = ($file->getExtension() ? '.' . $file->getExtension() : '');
            do {
                $file->name = uniqid() . $fileExtension;
            } while (file_exists($this->path . '/' . $file->name));

            $result = [];
            $result['name'] = $file->name;
            $result['type'] = $file->type;
            $result['size'] = $file->size;
            $result['url'] = $this->url . '/' . $file->name;

            if ($file->saveAs($this->path . '/' . $file->name) === false) {
                $result = ['error' => 'Failed to load file'];
                @unlink($file->tempName);
            } else {
                $result = ['files' => [$result]];
            }
        }

        if (is_callable($this->afterRun)) {
            $result = call_user_func($this->afterRun, $result);
        }

        return $result;
    }
}
