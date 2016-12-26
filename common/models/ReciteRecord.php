<?php

namespace common\models;

use yii\base\Exception;
use yii\db\ActiveRecord;

class ReciteRecord extends ActiveRecord
{
    public function table()
    {
        return 'public.recite_record';
    }

    public function rules()
    {
        return [
            [['user_id', 'topic', 'minutes', 'chapter_no', 'word_no', 'rate_of_progress', 'recite_date', 'created_at', 'topic_id'], 'required'],
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
     * 获取某人打卡天数
     * @param $user_id
     * @return array|bool
     */
    public function getClockDays($user_id)
    {
        $sql = "SELECT recite_date FROM public.recite_record WHERE user_id = 1 GROUP BY recite_date;";
        $sql = sprintf($sql, $user_id);
        return count(self::getDb()->createCommand($sql)->queryAll());
    }

    /**
     * 获取最后一次记录信息
     * @param $user_id
     * @return array|bool
     */
    public function findLastRecord($user_id)
    {
        return $this->find()->where(['user_id' => $user_id])->orderBy('rc_id DESC')->one();
    }
}