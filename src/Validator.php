<?php

namespace iAvatar777\widgets\DateTime;



use cs\services\VarDumper;
use yii\helpers\ArrayHelper;
use yii\helpers\FormatConverter;

class Validator extends \yii\validators\Validator
{
    /**
     * @param \iAvatar777\services\FormAjax\Model | \iAvatar777\services\FormAjax\ActiveRecord $model
     * @param string $attribute
     */
    public function validateAttribute($model, $attribute)
    {
        $fields = $model->attributeWidgets();
        $field = $fields[$attribute];
        $dateFormat = $field['dateFormat'];

        if (strncmp($dateFormat, 'php:', 4) === 0) {
            $dateFormat = substr($dateFormat, 4);
        } else {
            $dateFormat = FormatConverter::convertDateIcuToPhp($dateFormat);
        }

        if ($model->$attribute instanceof \DateTime) {

        } else {
            $f = FormatConverter::convertDatePhpToIcu($dateFormat);
            $this->addError($model, $attribute, 'Не верный формат. Должен быть: ' . $f);
        }
    }

    public function clientValidateAttribute($model, $attribute, $view)
    {

    }
}