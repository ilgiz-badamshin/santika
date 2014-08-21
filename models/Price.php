<?php

namespace app\models;

use app\models\scopes\PriceScope;
use PHPExcel_Cell;
use PHPExcel_IOFactory;
use Yii;
use yii\base\Exception;
use yii\db\BaseActiveRecord;

/**
 * This is the model class for table "price".
 *
 * @property integer $id
 * @property integer $shop_id
 * @property string $start_date
 * @property integer $type$id
 * @property integer $import_id
 *
 * @property PriceProduct[] $priceProducts
 * @property Upload $import
 */
class Price extends \yii\db\ActiveRecord
{
    const TYPE_PRODUCT = 1;
    const TYPE_SERVICE = 2;

    public $import_tmp;
    public $import_name;
    public $article_column;
    public $cost_column;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'price';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['shop_id', 'start_date', 'type'], 'required'],
            [['shop_id', 'type', 'import_id', 'article_column', 'cost_column'], 'integer'],
            [['start_date', 'type'], 'unique', 'targetAttribute' => ['shop_id', 'start_date', 'type'], 'message' => 'Прайс лист на выбранную дату уже существует.'],
            [['start_date', 'import_tmp', 'import_name'], 'safe'],
            [['article_column', 'cost_column'], 'required'
                , 'when' => function ($model, $attribute) {
                    return !empty($model->import_tmp);
                }
                , 'whenClient' => 'function (attribute, value) {
                    return  $("#price-import_tmp").val() != "";
                }',
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'shop_id' => 'Салон',
            'shop.name' => 'Салон',
            'start_date' => 'Начало действия прайса',
            'type' => 'Тип',
            'typeText' => 'Тип',
            'import_id' => 'Файл импорта',
            'article_column' => '№ столбца с артикулом',
            'cost_column' => '№ столбца с ценой',
        ];
    }

    /**
     * @inheritdoc
     * @return PriceScope
     */
    public static function find()
    {
        return new PriceScope(get_called_class());
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
        /**
         * @TODO при поиске в админке выполняются эти функции., мб придумать что то другое?
         */
        $this->saveFileFromAttribute('import', Upload::TYPE_PRICE);

        return parent::beforeValidate();
    }

    public function afterSave($insert, $changedAttributes)
    {
        /**
         * @TODO доп проверки на импорт
         */
        if ($this->type == static::TYPE_PRODUCT && !empty($this->import_id) && !empty($this->article_column) && !empty($this->cost_column)) {
            $this->importProductPrice();
        }
        parent::afterSave($insert, $changedAttributes);
    }

    public function beforeDelete()
    {
        /**
         * Проверка на существование связанных записей, что бы не было ошибок по FK
         */
        $errors = [];
        if ($this->getPriceProducts()->count() != 0) {
            $errors[] = 'Стоимтсть товара';
        }
        if (!empty($errors)) {
            $this->addError('id', 'Нельзя удалить, т.к. есть закрепленные ' . implode(', ', $errors));
            return false;
        }
        return parent::beforeDelete();
    }

    /**
     * Ипорт прайс листа из файла в поле import_id
     * @throws \yii\base\Exception
     */
    public function importProductPrice()
    {
        $file = $this->import->getFilePath();
        if (file_exists($file)) {
            //  Read your Excel workbook
            try {
                $inputFileType = PHPExcel_IOFactory::identify($file);
                $objReader = PHPExcel_IOFactory::createReader($inputFileType);
                $objPHPExcel = $objReader->load($file);
                $worksheet = $objPHPExcel->getActiveSheet();
                $highestRow = $worksheet->getHighestRow();
                $highestColumn = $worksheet->getHighestColumn();
                $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);
                if ($highestColumnIndex < $this->article_column || $highestColumnIndex < $this->cost_column) {
                    throw new Exception('Не корректный номер строки');
                }
                $start_row = 1;

                $curs_eur = Currency::getEurValue();
                $import_count = 0;
                $new_colors = 0;
                $new_products = 0;
                $error_count = 0;
                $colors = [];
                for ($row = $start_row; $row <= $highestRow; ++$row) {
                    $articles = $worksheet->getCellByColumnAndRow($this->article_column, $row)->getValue();
                    $cost_eur = $worksheet->getCellByColumnAndRow($this->cost_column, $row)->getValue();

                    $articles = explode('#', $articles);
                    if (empty($articles[0])) {
                        $error_count++;
                        continue;
                    }
                    $product_article = $articles[0];

                    $color_id = null;
                    if (isset($articles[1])) {
                        $color_article = $articles[1];
                        if (!array_key_exists($color_article, $colors)) {
                            $color = Color::find()->byArticle($color_article)->one();
                            if (is_null($color)) {
                                $color = new Color();
                                $color->name = 'Новое покрытие';
                                $color->article = $color_article;
                                $color->save();
                                $new_colors++;
                            }
                            $colors[$color_article] = $color->id;
                        }
                        $color_id = $colors[$color_article];
                    }

                    $product = Product::find()->byShop($this->shop_id)->byArticle($product_article)->one();
                    if (is_null($product)) {
                        $product = new Product();
                        $product->shop_id = $this->shop_id;
                        $product->name = 'Новый покрыттовар';
                        $product->description = 'Новый покрыттовар';
                        $product->article = $product_article;
                        $product->is_published = 0;
                        if ($product->save()) {
                            $new_products++;
                        } else {
                            $error_count++;
                        }
                    }

                    $pc = ProductColor::findOne(['product_id' => $product->id, 'color_id' => $color_id]);
                    if (is_null($pc)) {
                        $pc = new ProductColor();
                        $pc->product_id = $product->id;
                        $pc->color_id = $color_id;
                        $pc->save();
                    }

                    $price_product = PriceProduct::findOne(['price_id' => $this->id, 'product_id' => $product->id, 'color_id' => $color_id]);
                    if (empty($price_product)) {
                        $price_product = new PriceProduct();
                        $price_product->price_id = $this->id;
                        $price_product->product_id = $product->id;
                        $price_product->color_id = $color_id;
                    }
                    $price_product->cost_eur = $cost_eur;
                    $price_product->cost_rub = $cost_eur * $curs_eur;
                    $price_product->save();
                    $import_count++;
                }
                Yii::$app->getSession()->setFlash('importPrice', "Импортировано цен: $import_count. Новых товаров: $new_products. Новых покрытий: $new_colors. Ошибок: $error_count ");
            } catch (Exception $e) {
                throw new Exception($e);
            }
        }
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPriceProducts()
    {
        return $this->hasMany(PriceProduct::className(), ['price_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getShop()
    {
        return $this->hasOne(Shop::className(), ['id' => 'shop_id']);
    }

    public static function getTypesText()
    {
        return [
            static::TYPE_PRODUCT => 'Товары',
            static::TYPE_SERVICE => 'Услуги',
        ];
    }

    public function getTypeText()
    {
        if (empty($this->type)) {
            return null;
        }
        return self::getTypesText()[$this->type];
    }


    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImport()
    {
        return $this->hasOne(Upload::className(), ['id' => 'import_id']);
    }
}
