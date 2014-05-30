<?php
/**
 * Created by PhpStorm.
 * User: KURT
 * Date: 24.05.14
 * Time: 14:55
 */

namespace app\behaviors;


use app\models\Upload;
use yii\base\Behavior;

class FileSaveBehavior extends Behavior
{


    /**
     * Сохраняет файл из временной папки в Uploads
     * @param $attribute
     * @param $file_type
     */
    public function saveFileFromAttribute($attribute, $file_type)
    {
        $attr_tmp = $attribute . '_tmp';
        $attr_name = $attribute . '_name';
        $attr_id = $attribute . '_id';
        $model = $this->owner;
        if (!empty($model->$attr_tmp)) {
            if (empty($model->$attr_id)) {
                $upload = new Upload();
            } else {
                $upload = Upload::findOne($model->$attr_id);
            }
            $name = $model->$attr_name;

            $uploads_dir = Upload::getUploadsPath();
            //путь до временного файла
            $source_folder = Upload::getUploadsPathByType(Upload::TYPE_TMP);
            $source = $uploads_dir . $source_folder . $model->$attr_tmp;

            $dest_folder = Upload::getUploadsPathByType($file_type);
            $unique_name = Upload::getUniquerName($uploads_dir . $dest_folder, $name);
            $path = $dest_folder . $unique_name;
            $dest = $uploads_dir . $path;
            if (!copy($source, $dest)) {
                /**
                 * @TODO обработать ошибку
                 */
            }


            //переносим миниатуюру то же
            //путь до временного файла
            $source_thumb_folder = Upload::getUploadsPathByType(Upload::TYPE_TMP, true);
            $source_thumb = $uploads_dir . $source_thumb_folder . $model->$attr_tmp;

            $dest_thumb_folder = Upload::getUploadsPathByType($file_type, true);
            $dest_thumb = $uploads_dir . $dest_thumb_folder . $unique_name;

            if (!copy($source_thumb, $dest_thumb)) {
                /**
                 * @TODO обработать ошибку
                 */
            }

            $upload->name = $name;
            $upload->path = $path;
            $upload->ext = '';
            $upload->save();
            $model->$attr_id = $upload->id;
        }
    }
} 