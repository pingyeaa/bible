<?php

namespace common\models;

use yii\db\ActiveRecord;

class ApiLog extends ActiveRecord
{
    public function table()
    {
        return 'public.api_log';
    }

    public function rules()
    {
        return [
            [['params', 'route', 'request_type', 'url', 'response', 'status', 'memory', 'response_time', 'ip', 'created_at'], 'required']
        ];
    }

    public function add($data)
    {
        $this->isNewRecord = true;
        $this->attributes = $data;
        return $this->save();
    }
}