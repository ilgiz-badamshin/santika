<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "collection".
 *
 * @property integer $id
 * @property integer $shop_id
 * @property string $name
 * @property string $description
 * @property integer $sort
 * @property string $url
 * @property string $meta_title
 * @property string $meta_description
 * @property string $meta_keywords
 */
class Collection extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'collection';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['shop_id', 'name', 'description', 'url'], 'required'],
            [['shop_id', 'sort'], 'integer'],
            [['name', 'description', 'url', 'meta_title', 'meta_description', 'meta_keywords'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'shop_id' => Yii::t('app', 'Shop ID'),
            'name' => Yii::t('app', 'Name'),
            'description' => Yii::t('app', 'Description'),
            'sort' => Yii::t('app', 'Sort'),
            'url' => Yii::t('app', 'Url'),
            'meta_title' => Yii::t('app', 'Meta Title'),
            'meta_description' => Yii::t('app', 'Meta Description'),
            'meta_keywords' => Yii::t('app', 'Meta Keywords'),
        ];
    }
}
