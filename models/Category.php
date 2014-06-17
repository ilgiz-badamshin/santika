<?php

namespace app\models;

use app\models\scopes\CategoryScope;
use Yii;
use yii\base\Exception;
use yii\db\BaseActiveRecord;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;

/**
 * This is the model class for table "category".
 *
 * @property integer $id
 * @property integer $shop_id
 * @property integer $parent_id
 * @property string $name
 * @property integer $sort
 * @property string $url
 * @property string $meta_title
 * @property string $meta_description
 * @property string $meta_keywords
 *
 * @property integer[] $line_ids
 * @property integer[] $old_line_ids
 *
 * @property Category $parent
 * @property Category[] $childs
 * @property Shop $shop
 * @property LineCategory[] $lineCategories
 * @property Product[] $products
 */
class Category extends \yii\db\ActiveRecord
{
    public $use_related_ids = false;
    public $line_ids = [];
    public $old_line_ids = [];
    public $parent_name;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'category';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['shop_id', 'name', 'url'], 'required'],
            [['shop_id', 'parent_id', 'sort'], 'integer'],
            [['name', 'url', 'meta_title', 'meta_description', 'meta_keywords'], 'string', 'max' => 255],
            [['line_ids'], 'safe']
        ];
    }
    /**
     * @TODO сделать проверку parent_id как в collection
     */

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'shop_id' => 'Салон', //Yii::t('app', 'Shop ID'),
            'shop.name' => 'Салон', //Yii::t('app', 'Shop ID'),
            'parent_id' => 'Родительская категория', //Yii::t('app', 'Parent ID'),
            'parent.name' => 'Родительская категория', //Yii::t('app', 'Parent ID'),
            'parent_name' => 'Родительская категория', //Yii::t('app', 'Parent ID'),
            'name' => 'Название', //Yii::t('app', 'Name'),
            'sort' => 'Сортировка', //Yii::t('app', 'Sort'),
            'line_ids' => 'Линии',
            'url' => Yii::t('app', 'Url'),
            'meta_title' => Yii::t('app', 'Meta Title'),
            'meta_description' => Yii::t('app', 'Meta Description'),
            'meta_keywords' => Yii::t('app', 'Meta Keywords'),
        ];
    }


    public function beforeValidate()
    {
        $this->url = Inflector::slug($this->name);
        return parent::beforeValidate();
    }


    public function afterSave($insert)
    {
        if ($this->use_related_ids) {
            $this->saveLines();
        }
    }

    public function beforeDelete()
    {
        /**
         * Проверка на существование связанных записей, что бы не было ошибок по FK
         */
        $errors = [];
        if ($this->getLineCategories()->count() != 0) {
            $errors[] = 'Связь линия-категория';
        }
        if ($this->getProducts()->count() != 0) {
            $errors[] = 'Товары';
        }
        if ($this->getChilds()->count() != 0) {
            $errors[] = 'Категории';
        }
        if (!empty($errors)) {
            $this->addError('id', 'Нельзя удалить, т.к. есть закрепленные ' . implode(', ', $errors));
            return false;
        }

        return parent::beforeDelete();
    }

    /**
     * Подготавливает массив линий
     */
    public function prepare()
    {
        $this->use_related_ids = 1;
        $this->prepareLines();
    }

    /**
     * Вытаскивает все линии товара
     */
    public function prepareLines()
    {
        $line_categories = LineCategory::findAll(['category_id' => $this->id]);
        $line_ids = [];
        foreach ($line_categories as $line_category) {
            $line_ids[] = $line_category->line_id;
        }
        $this->line_ids = $line_ids;
        $this->old_line_ids = $line_ids;
    }

    /**
     * Сохраняет разницу в выбранных линиях
     */
    public function saveLines()
    {
        $line_ids = $this->line_ids == "" ? [] : $this->line_ids;
        $diff_delete = array_diff($this->old_line_ids, $line_ids);
        $diff_insert = array_diff($line_ids, $this->old_line_ids);
        LineCategory::deleteAll(['category_id' => $this->id, 'line_id' => $diff_delete]);
        if (!empty($this->line_ids)) {
            foreach ($diff_insert as $line_id) {
                $lc = new LineCategory();
                $lc->category_id = $this->id;
                $lc->line_id = $line_id;
                $lc->save();
            }
        }
    }


    /**
     * @inheritdoc
     * @return CategoryScope
     */
    public static function find()
    {
        return new CategoryScope(get_called_class());
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getParent()
    {
        return $this->hasOne(Category::className(), ['id' => 'parent_id'])->
            from(self::tableName() . ' AS parent');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getChilds()
    {
        return $this->hasMany(Category::className(), ['parent_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getShop()
    {
        return $this->hasOne(Shop::className(), ['id' => 'shop_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLineCategories()
    {
        return $this->hasMany(LineCategory::className(), ['category_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProducts()
    {
        return $this->hasMany(Product::className(), ['category_id' => 'id']);
    }

    /**
     * Возвращает категории которые не прикреплены к линии или которые находятся в текущей связи линия-категория
     * @param $shop_id
     * @param $line_category_id
     * @return Category[]
     * @deprecated
     */
    public static function withOutLine($shop_id, $line_category_id)
    {
        $query_for_id = (new Query)->select('category_id')->from('line_category');
        if (!empty($line_category_id)) {
            $query_for_id->andWhere(['not in', 'id', $line_category_id]);
        }
        $query = Category::find();
        $query->select = ['id', 'name'];
        $query->andWhere(['not in', 'id', $query_for_id]);
        return $query->byShop($shop_id)->all();
    }

    /**
     * Возвращает массив категорий находящихся в указанных линиях
     * @param int $shop_id
     * @param array|Query $line_ids
     * @throws \yii\base\Exception
     * @return Category[]
     */
    public static function byLineIds($shop_id, $line_ids, $skip_id = false)
    {
        $query_for_category_id = (new Query)->select('category_id')->from('line_category');
        if (is_array($line_ids)) {
            $query_for_category_id->where(['line_id' => $line_ids]);
        } elseif ($line_ids instanceof Query) {
            $query_for_category_id->where(['in', 'line_id', $line_ids]);
        } else {
            return [];
        }
        $query = Category::find();
        $query->select = ['id', 'name'];
        $query->andWhere(['in', 'id', $query_for_category_id]);
        if ($skip_id) {
            $query->andWhere('id != :skip_id', [':skip_id' => $skip_id]);
        }
        return $query->byShop($shop_id)->all();
    }

    /**
     * Возвращает массив категорий находящихся в линиях выбранного продукта
     * @param $shop_id
     * @param $product_id
     * @return Category[]
     */
    public static function byProductId($shop_id, $product_id)
    {
        $query_for_line_id = (new Query)->select('line_id')->from('line_product')->where(['product_id' => $product_id]);
        return self::byLineIds($shop_id, $query_for_line_id);

    }
}
