<?php

namespace common\models;

use yii\db\ActiveRecord;

class ReciteTopic extends ActiveRecord
{
    public function table()
    {
        return 'public.recite_topic';
    }

    public function rules()
    {
        return [
            [['topic_id', 'topic_name', 'verse_total'], 'required'],
        ];
    }

    public static function findById($topic_id)
    {
        return self::find()->where(['topic_id' => $topic_id])->one();
    }
}