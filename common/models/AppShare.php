<?php

namespace common\models;

use yii\base\Exception;
use yii\db\ActiveRecord;

class AppShare extends ActiveRecord
{
    public function table()
    {
        return 'public.app_share';
    }

    public function rules()
    {
        return [
            [['user_id', 'share_times', 'created_at', 'updated_at'], 'required'],
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

    public static function findByUserId($userId)
    {
        return self::find()->where(['user_id' => $userId])->orderBy('id desc')->one();
    }

    /**
     * 递增分享次数
     * @param $userId
     * @throws Exception
     * @return array
     */
    public function accumulation($userId)
    {
        $sql = "update public.app_share set share_times = share_times + 1 where user_id = %d";
        $sql = sprintf($sql, $userId);
        $is = $this->getDb()->createCommand($sql)->execute();
        if(!$is)
            throw new Exception(json_encode($this->getErrors()));
        return true;
    }
}