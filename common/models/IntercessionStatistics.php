<?php

namespace common\models;

use yii\base\Exception;
use yii\db\ActiveRecord;

class IntercessionStatistics extends ActiveRecord
{
    public function table()
    {
        return 'public.intercession_statistics';
    }

    public function rules()
    {
        return [
            [['user_id', 'continuous_interces_days', 'last_interces_time', 'created_at', 'updated_at'], 'required']
        ];
    }

    public function add($data)
    {
        $this->isNewRecord = true;
        $this->attributes = $data;
        if(!$this->save())
            throw new Exception(json_encode($this->getErrors()));
        return true;
    }

    /**
     * @param $userId
     * @return int
     */
    public static function deleteInfo($userId)
    {
        return self::deleteAll(['user_id' => $userId]);
    }

    /**
     * @param $userId
     * @return array|null|ActiveRecord
     */
    public static function findWithUserId($userId)
    {
        return self::find()->where(['user_id' => $userId])->one();
    }
}