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
"lav45/yii2-file-upload-module": "^1.1"
```

to the require section of your `composer.json` file.


Basic Usage:
------

Add path aliases and url to your file store in the main config
You need to configure your web server to the `@storageDir` directory and specify `@storageUrl`
```php
return [
    'aliases' => [
        '@storageUrl' => 'https://cdn.site.com/storage',
    ],
    'components' => [
        'fs' => [
            'class' => diecoding\flysystem\LocalComponent::className(),
            'path' => '@common/cdn',
            'secret' => 'secret'
        ]
    ],
];
```

Add action to the main controller
```php
use lav45\fileUpload\UploadAction;

class PageController extends Controller
{
    public function actions()
    {
        return [
            'upload' => [
                'class' => UploadAction::className(),
            ],
        ];
    }
}
```

Need to add to your ActiveRecord model
```php
use lav45\fileUpload\UploadTrait;
use lav45\fileUpload\UploadBehavior;
use lav45\fileUpload\UploadInterface;

class Page extends ActiveRecord implements UploadInterface
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
            [
                'class' => UploadBehavior::className(),
                'attribute' => 'image',
            ],
        ];
    }
    
    public function getUploadPath()
    {
        return '/page/' . $this->id;
    }
}
```

Need to add a field for uploading files
```php
/**
 * @var Page $model
 */
 
use lav45\fileUpload\widget\FileUpload;

$form = ActiveForm::begin();

echo $form->field($model, 'image')->widget(FileUpload::className());

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
