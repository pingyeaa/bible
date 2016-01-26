<?php

namespace common\models;

use yii\db\ActiveRecord;

class Portrait extends ActiveRecord
{
    public function table()
    {
        return 'public.portrait';
    }

    public function rules()
    {
        return [
            [['user_id', 'portrait_name', 'created_at'], 'required']
        ];
    }

    public function add($data)
    {
        $this->isNewRecord = true;
        $this->attributes = $data;
        return $this->save();
    }
}