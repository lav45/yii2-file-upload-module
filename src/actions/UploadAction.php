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
 *             'class' => 'lav45\fileUpload\actions\UploadAction',
 *             'url' => '@web/assets/upload',
 *             'path' => '@webroot/assets/upload',
 *             'validatorOptions' => [
 *                 'maxWidth' => 1000,
 *                 'maxHeight' => 1000
 *             ]
 *         ],
 *         'file-upload' => [
 *             'class' => 'lav45\fileUpload\actions\UploadAction',
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
    public $path;
    /**
     * @var string URL path to directory where files will be uploaded
     */
    public $url = '@web/assets/upload';
    /**
     * @var string Model validator name
     */
    private $validator = 'image';

    /**
     * Initializes the object.
     */
    public function init()
    {
        parent::init();

        $this->setPath($this->path);
        $this->setUrl($this->url);
    }

    /**
     * @param string $path
     * @throws InvalidConfigException
     */
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

    /**
     * @param string $url
     * @throws InvalidConfigException
     */
    public function setUrl($url)
    {
        if (empty($url)) {
            throw new InvalidConfigException('The "url" attribute must be set.');
        }

        $url = Yii::getAlias($url);
        $this->url = rtrim($url, '/');
    }

    /**
     * @param boolean $flag
     */
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
            $original_name = $file->name;
            do {
                $file->name = uniqid() . $fileExtension;
            } while (file_exists($this->path . '/' . $file->name));

            $result = [
                'original_name' => $original_name,
                'name' => $file->name,
                'type' => $file->type,
                'size' => $file->size,
                'url' => $this->url . '/' . $file->name,
            ];

            if ($file->saveAs($this->path . '/' . $file->name) === false) {
                $result = ['error' => 'Failed to load file'];
                @unlink($file->tempName);
            }
        }

        if (is_callable($this->afterRun)) {
            $result = call_user_func($this->afterRun, $result);
        }

        return $result;
    }
}
