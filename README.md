yii2-file-upload-module
==============================

This is a module for the Yii2 Framework which will help you upload files and access them from the browser.


Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
~$ composer require --prefer-dist lav45/yii2-file-upload-module
```

or add

```
"lav45/yii2-file-upload-module": "^0.1"
```

to the require section of your `composer.json` file.


Basic Usage:
------

Add path aliases and url to your file store in the main config
```php
return [
    'aliases' => [
        '@storagePath' => '/path/to/upload/dir',
        '@storageUrl' => '/url/to/upload/dir',
    ],
];
```

Add action to the main controller
```php
use lav45\fileUpload\actions\UploadAction;

class PageController extends Controller
{
    public function actions()
    {
        return [
            'upload' => [
                'class' => UploadAction::className(),
                'path' => Page::getTempDir(),
                //'uploadOnlyImage' => false,
            ],
        ];
    }
    
    // ...
}
```

Need to add to your ActiveRecord model
```php
use lav45\fileUpload\traits\UploadTrait;
use lav45\fileUpload\behaviors\UploadBehavior;

class Page extends ActiveRecord
{
    use UploadTrait;

    public function rules()
    {
        return [
            [['image'], 'string'],
        ];
    }

    public function behaviors()
    {
        return [
            'uploadBehavior' => [
                'class' => UploadBehavior::className(),
                'uploadDir' => $this->getUploadDir(),
                'tempDir' => $this->getTempDir(),
                'attribute' => 'image',
            ],
        ];
    }
}
```

Need to add a field for uploading files
```php
use lav45\fileUpload\widgets\FileUpload;

$form = ActiveForm::begin();

echo $form->field($model, 'image')->widget(FileUpload::className());

// ...

ActiveForm::end();
```

Displays the uploaded file
```php
<?php
/**
 * @var Page $model
 */
 ?>
 
<img src="<?= $model->getAttributeUrl('image') ?>" alt="">
```
