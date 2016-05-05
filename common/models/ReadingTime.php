<?php

namespace common\models;

use yii\db\ActiveRecord;

class ReadingTime extends ActiveRecord
{
    public function table()
    {
        return 'public.reading_time';
    }

    public function rules()
    {
        return [
            [['user_id', 'total_minutes', 'last_minutes', 'continuous_days', 'created_at', 'updated_at', 'yesterday_minutes', 'today_minutes', 'last_read_long'], 'required']
        ];
    }

    public function add($data)
    {
        $this->isNewRecord = true;
        $this->attributes = $data;
        return $this->save();
    }

    public static function findByUserId($userId)
    {
        return self::find()->where(['user_id' => $userId])->orderBy('id desc')->one();
    }

    /**
     * 修改数据
     * @param $data
     * @param $userId
     * @return int
     */
    public static function mod($data, $userId)
    {
        return self::updateAll($data, 'user_id = :user_id', ['user_id' => $userId]);
    }
}