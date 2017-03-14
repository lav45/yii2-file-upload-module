<?php
/**
 * @var yii\web\View $this
 */

use yii\helpers\Html;

/** @var \lav45\fileUpload\widgets\FileUpload $widget */
$widget = $this->context;

$widget->options['id'] .= '-uploader';

$targetId = $widget->hasModel() ? Html::getInputId($widget->model, $widget->attribute) : $widget->getId();

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

$input = $widget->hasModel() ?
    Html::activeHiddenInput($widget->model, $widget->attribute) :
    Html::hiddenInput($widget->name, $widget->value, ['id' => $widget->getId()]);

$input .= Html::fileInput('file', null, $widget->options);

$value = $widget->hasModel() ? Html::getAttributeValue($widget->model, $widget->attribute) : $widget->value;

$url = $widget->hasModel() ? $widget->model->getAttributeUrl($widget->attribute) : null;

?>

<div class="input-group">
    <?= $input ?>
    <span class="input-group-addon input-result">
        <?php if ($url): ?>
            <a href="<?= $url ?>" target="_blank"><?= $value ?></a>
        <?php else: ?>
            <?= $value ?>
        <?php endif; ?>
    </span>
</div>