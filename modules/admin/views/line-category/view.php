<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/**
 * @var yii\web\View $this
 * @var app\models\LineCategory $model
 */

$this->title = $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Связь линия-категория', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="line-category-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Редактировать', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?=
        Html::a('Удалить', ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger ajax-delete',
        ]) ?>
    </p>

    <?=
    DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'line.name',
            'category.name',
        ],
    ]) ?>

</div>
