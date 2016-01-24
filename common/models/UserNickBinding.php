<?php

namespace common\models;

use yii\db\ActiveRecord;

class UserNickBinding extends ActiveRecord
{
    public function table()
    {
        return 'public.user_nick_binding';
    }

    public function rules()
    {
        return [
            [['user_id', 'nick_list_id', 'create_at'], 'required']
        ];
    }

    public function add($data)
    {
        $this->isNewRecord = true;
        $this->attributes = $data;
        return $this->save();
    }
}