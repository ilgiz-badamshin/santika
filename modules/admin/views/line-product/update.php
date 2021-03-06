<?php

use yii\helpers\Html;

/**
 * @var int $shop_id
 * @var yii\web\View $this
 * @var app\models\LineProduct $model
 */

$this->title = 'Редактирование ' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Связь линия-товар', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Редактирование';
?>
<div class="line-product-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?=
    $this->render('_form', [
        'model' => $model,
        'shop_id' => $shop_id,
    ]) ?>

</div>
