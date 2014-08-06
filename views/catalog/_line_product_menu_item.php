<?php
/**
 * Created by PhpStorm.
 * User: ilgiz
 * Date: 06.08.14
 * Time: 10:22
 * @var $object_type
 * @var $parent_type
 * @var $parent_id
 * @var $level
 * @var Collection[]|Category[] $models
 */
use app\models\Category;
use app\models\Collection;
use app\models\Upload;

?>

<div class="b-carusel hidden <?= 'level' . $level ?>"
     parent_type="<?= $parent_type; ?>"
     parent_id="<?= $parent_id ?>">
    <?php if (count($models) > 0) : ?>
        <div class="gallery">
            <ul>
                <?php foreach ($models as $model): ?>
                    <li class="show_childs"
                        object_type="<?= $object_type ?>"
                        object_id="<?= $model->id ?>"
                        hide_level="<?= 'level' . ($level + 1) ?>"
                        >
                        <a href="">
                            <?php $src = !empty($model->photo_id) ? $model->photo->getFileShowUrl(true) : Upload::defaultFileUrl(true) ?>
                            <div><img src="<?= $src; ?>"></div>
                            <?= $model->name; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <div class="nav">
                <button class="prev"></button>
                <button class="next"></button>
            </div>
        </div>
        <div>
            <?php foreach ($models as $model): ?>
                <?php if ($level < 3 && $model->getChilds()->count() > 0): ?>
                    <?php
                    echo $this->render('/catalog/_line_product_menu_item', array(
                        'object_type' => $object_type,
                        'parent_type' => $object_type,
                        'parent_id' => $model->id,
                        'level' => $level + 1,
                        'models' => $model->childs,
                    ));
                    ?>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>