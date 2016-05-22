<?php

namespace common\models;

use yii\base\Exception;
use yii\db\ActiveRecord;

class AskedDaily extends ActiveRecord
{
    public function table()
    {
        return 'public.asked_daily';
    }

    public function rules()
    {
        return [
            [['title', 'created_at', 'updated_at', 'status', 'url'], 'required'],
            [['content'], 'safe'],
        ];
    }

    public function add($data)
    {
        $this->isNewRecord = true;
        $this->attributes = $data;
        $is = $this->save();
        if(!$is) {
            throw new Exception(json_encode($this->getErrors()));
        }
        return $is;
    }

    /**
     * @return array|null|ActiveRecord
     */
    public static function findLasted()
    {
        return self::find()->where(['status' => 0])->orderBy('id asc')->one();
    }
}