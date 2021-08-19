<?php
/**
 * @var yii\web\View $this
 */

use yii\helpers\Html;
use lav45\fileUpload\UploadInterface;

/** @var \lav45\fileUpload\widget\FileUpload $widget */
$widget = $this->context;

$widget->options['id'] .= '-uploader';

$targetId = $widget->model ? Html::getInputId($widget->model, $widget->attribute) : $widget->getId();

$widget->addClientEvents('fileuploaddone', <<<JS
function(e, data) {
    if (data.result.error) {
        alert(data.result.error);
    } else {
        var el = jQuery('#{$targetId}'),
            fileName = data.result.name,
            fileUrl = data.result.url;
         
        el.val(fileName);
        
        var link = jQuery('<a>')
            .text(fileName)
            .attr({
                href: fileUrl,
                target: '_blank'
            });

        el.parent().find('.input-result').html(link);
    }
}
JS
);

Html::addCssClass($widget->options, 'form-control');

$input = $widget->model ?
    Html::activeHiddenInput($widget->model, $widget->attribute) :
    Html::hiddenInput($widget->name, $widget->value, ['id' => $widget->getId()]);

$input .= Html::fileInput('file', null, $widget->options);

$value = $widget->model ? Html::getAttributeValue($widget->model, $widget->attribute) : $widget->value;
$value = Html::encode($value);

?>

<div class="input-group">
    <?= $input ?>
    <span class="input-group-addon input-result">
        <?php
        if ($widget->model instanceof UploadInterface) {
            $url = $widget->model->getAttributeUrl($widget->attribute);
            echo Html::a($value, $url, ['target' => '_blank']);
        } else {
            echo $value;
        }
        ?>
    </span>
</div>