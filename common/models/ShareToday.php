<?php

namespace common\models;

use yii\db\ActiveRecord;

class ShareToday extends ActiveRecord
{
    public function table()
    {
        return 'public.share_today';
    }

    public function rules()
    {
        return [
            [['title', 'share_content', 'share_number', 'created_at', 'updated_at'], 'required']
        ];
    }

    public function add($data)
    {
        $this->isNewRecord = true;
        $this->attributes = $data;
        return $this->save();
    }

    /**
     * 查找最新信息
     * @return array|null|ActiveRecord
     */
    public static function findNewInfo()
    {
        return self::find()->orderBy('id desc')->one();
    }
}