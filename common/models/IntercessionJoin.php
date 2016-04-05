<?php

namespace common\models;

use yii\db\ActiveRecord;

class IntercessionJoin extends ActiveRecord
{
    public function table()
    {
        return 'public.intercession_join';
    }

    public function rules()
    {
        return [
            [['intercession_id', 'user_id', 'intercessors_id', 'ip', 'created_at', 'updated_at'], 'required']
        ];
    }

    public function add($data)
    {
        $this->isNewRecord = true;
        $this->attributes = $data;
        return $this->save();
    }

    public static function getAllByIntercessionId($intercessionId)
    {
        return self::find()->where(['intercession_id' => $intercessionId])->orderBy('praise_number desc')->all();
    }

    /**
     * @param $intercessionId
     * @param $intercessorsId
     * @return $this|static
     */
    public static function findByIntercessionIdAndIntercessorsId($intercessionId, $intercessorsId)
    {
        return self::find()->where(['intercession_id' => $intercessionId, 'intercessors_id' => $intercessorsId])->one();
    }
}