<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "color".
 *
 * @property integer $id
 * @property string $name
 * @property integer $upload_id
 *
 * @property Upload $upload
 */
class Color extends \yii\db\ActiveRecord
{
    public $upload_tmp;
    public $upload_name;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'color';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['upload_id'], 'integer'],
            [['name'], 'string', 'max' => 255],
            [['upload_tmp', 'upload_name'], 'safe']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'name' => 'Наименование',
            'upload_id' => 'Изображение',
            'upload.name' => 'Изображение',
            'upload_name' => 'Изображение',
            'upload.fileShowLink' => 'Изображение',
        ];
    }

    public function behaviors()
    {
        return [
            'fileSaveBehavior' => [
                'class' => 'app\behaviors\FileSaveBehavior',
            ]
        ];
    }

    public function beforeValidate()
    {
        $this->saveFileFromAttribute('upload', Upload::TYPE_COLOR);
        return parent::beforeValidate();
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUpload()
    {
        return $this->hasOne(Upload::className(), ['id' => 'upload_id']);
    }
}
