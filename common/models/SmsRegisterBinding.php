<?php

namespace common\models;

use yii\db\ActiveRecord;

class SmsRegisterBinding extends ActiveRecord
{
    public function table()
    {
        return 'public.sms_register_binding';
    }

    public function rules()
    {
        return [
            [['nation_code', 'phone', 'code', 'status', 'create_at'], 'required']
        ];
    }

    public function add($data)
    {
        $this->isNewRecord = true;
        $this->attributes = $data;
        return $this->save();
    }
}