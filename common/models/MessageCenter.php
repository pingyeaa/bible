<?php

namespace common\models;

use yii\db\ActiveRecord;

class MessageCenter extends ActiveRecord
{
    public function table()
    {
        return 'public.message_center';
    }

    public function rules()
    {
        return [
            [['to_user', 'msg_title', 'msg_content', 'created_time', 'msg_type'], 'safe']
        ];
    }

    public function add($data)
    {
        $this->isNewRecord = true;
        $this->attributes = $data;
        return $this->save();
    }
}