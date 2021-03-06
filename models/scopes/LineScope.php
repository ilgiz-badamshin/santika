<?php
/**
 * Created by PhpStorm.
 * User: KURT
 * Date: 19.05.14
 * Time: 0:07
 */

namespace app\models\scopes;


use yii\db\ActiveQuery;

class LineScope extends ActiveQuery
{
    /**
     * @param integer $shop_id
     * @return $this
     */
    public function byShop($shop_id)
    {
        $this->andWhere(['shop_id' => $shop_id]);
        return $this;
    }

    /**
     * @param $url
     * @return $this
     */
    public function byUrl($url)
    {
        $this->andWhere(['url' => $url]);
        return $this;
    }
} 