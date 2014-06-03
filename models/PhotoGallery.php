<?php

namespace app\models;

use Yii;
use yii\base\ModelEvent;
use yii\db\BaseActiveRecord;
use yii\helpers\Html;

/**
 * This is the model class for table "photo_gallery".
 *
 * @property integer $id
 * @property integer $object_id
 * @property integer $upload_id
 * @property integer $type
 * @property integer $sort
 *
 * @property Upload $upload
 */
class PhotoGallery extends \yii\db\ActiveRecord
{
    const TYPE_PRODUCT = 1;
    const TYPE_PROJECT = 2;

    public $upload_tmp;
    public $upload_name;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'photo_gallery';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['object_id', 'upload_id', 'type', 'sort'], 'required'],
            [['object_id', 'upload_id', 'type', 'sort'], 'integer']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'object_id' => Yii::t('app', 'Object ID'),
            'upload_id' => Yii::t('app', 'Upload ID'),
            'type' => Yii::t('app', 'Type'),
            'sort' => Yii::t('app', 'Sort'),
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
        $this->saveFileFromAttribute('upload', Upload::TYPE_PRODUCT);

        return parent::beforeValidate();
    }

    public function afterDelete()
    {
        if (!empty($this->upload_id)) {
            $this->upload->delete();
        }
        parent::afterDelete();
    }


    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUpload()
    {
        return $this->hasOne(Upload::className(), ['id' => 'upload_id']);
    }

    public function renderSortItem()
    {
        $html = '';
        $html .= Html::img($this->upload->getFileShowUrl(true));
        $html .= Html::a('Удалить', '#', ['class' => 'delete-photo_gallery']);

        $html = Html::tag('div', $html, ['photo_gallery-id' => $this->id, 'sort' => $this->sort]);
        return $html;
    }

}
