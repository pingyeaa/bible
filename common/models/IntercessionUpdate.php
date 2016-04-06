<?php

namespace common\models;

use yii\base\Exception;
use yii\db\ActiveRecord;

class IntercessionUpdate extends ActiveRecord
{
    public function table()
    {
        return 'public.intercession_update';
    }

    public function rules()
    {
        return [
            [['intercession_id', 'content', 'ip', 'created_at', 'updated_at'], 'required']
        ];
    }

    public function add($data)
    {
        $this->isNewRecord = true;
        $this->attributes = $data;
        $is = $this->save();
        if(!$is)
            throw new Exception(json_encode($this->getErrors()));
        return true;
    }

    /**
     * @param $intercessionId
     * @return array|\yii\db\ActiveRecord[]
     */
    public static function getListWithIntercessionId($intercessionId)
    {
        return self::find()->where(['intercession_id' => $intercessionId])->orderBy('id desc')->all();
    }
}