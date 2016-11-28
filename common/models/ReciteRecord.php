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
            [['user_id', 'topic', 'minutes', 'chapter_no', 'word_no', 'rate_of_progress', 'recite_date', 'created_at'], 'required'],
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
}