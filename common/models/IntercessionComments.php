<?php

namespace common\models;

use yii\db\ActiveRecord;

class IntercessionComments extends ActiveRecord
{
    public function table()
    {
        return 'public.intercession_comments';
    }

    public function rules()
    {
        return [
            [['intercession_id', 'user_id', 'comment_by_id', 'content', 'praise_number', 'ip', 'created_at', 'updated_at'], 'required']
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
}