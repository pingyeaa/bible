<?php

namespace common\models;

use yii\db\ActiveRecord;

class Volume extends ActiveRecord
{
    public function table()
    {
        return 'public.volume';
    }

    public function rules()
    {
        return [
        ];
    }
}