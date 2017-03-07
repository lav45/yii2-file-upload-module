<?php

namespace lav45\fileUpload\widgets;

use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\widgets\InputWidget;

/**
 * Class FileUpload
 * @package lav45\fileUpload\widgets
 */
class FileUpload extends InputWidget
{
    /**
     * @var string|array upload route
     */
    public $url;
    /**
     * @var array the plugin options. For more information see the jQuery File Upload options documentation.
     * @see https://github.com/blueimp/jQuery-File-Upload/wiki/Options
     */
    public $clientOptions = [];
    /**
     * @var array the event handlers for the jQuery File Upload plugin.
     * Please refer to the jQuery File Upload plugin web page for possible options.
     * @see https://github.com/blueimp/jQuery-File-Upload/wiki/Options#callback-options
     */
    public $clientEvents = [];

    public function init()
    {
        parent::init();

        $this->clientOptions['url'] = Url::to($this->url);
        $this->options['data-url'] = $this->clientOptions['url'];
        $this->options['id'] .= '-uploader';
    }

    public function run()
    {
        $this->registerAssets();
        $this->registerClientScript();
        return $this->renderInput();
    }
    
    protected function renderInput()
    {
        $input = $this->hasModel() ?
            Html::activeHiddenInput($this->model, $this->attribute) :
            Html::hiddenInput($this->name, $this->value, ['id' => $this->getId()]);

        $input .= Html::fileInput('file', null, $this->options);

        return $input;
    }

    protected function registerAssets()
    {
        assets\FileUploadAsset::register($this->getView());
    }

    protected function registerClientScript()
    {
        $options = Json::encode($this->clientOptions);
        $id = $this->options['id'];

        $targetId = $this->hasModel() ? Html::getInputId($this->model, $this->attribute) : $this->getId();

        $js[] = "jQuery('#{$id}').on('fileuploaddone', function(e, data) {
            jQuery('#{$targetId}').val(data.result.files[0].name);
        });";

        $js[] = "jQuery('#{$id}').fileupload({$options});";

        foreach ($this->clientEvents as $event => $handler) {
            $js[] = "jQuery('#{$id}').on('{$event}', {$handler});";
        }

        $this->getView()->registerJs(implode("\n", $js));
    }
}
