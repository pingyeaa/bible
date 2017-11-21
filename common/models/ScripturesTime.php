<?php

namespace common\models;

use yii\db\ActiveRecord;

class ScripturesTime extends ActiveRecord
{
    public function table()
    {
        return 'public.scriptures_time';
    }

    public function rules()
    {
        return [
            ['volume_id', 'chapter_no', 'seconds']
        ];
    }

    public function add($data)
    {
        $this->isNewRecord = true;
        $this->attributes = $data;
        return $this->save();
    }
}