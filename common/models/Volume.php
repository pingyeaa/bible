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

    public static function volumeList()
    {
        $sql = "
            select a.full_name, b.volume_id, b.chapter_no from public.volume a 
            inner join public.scriptures b on a.id = b.volume_id 
            group by b.volume_id, b.chapter_no, a.full_name order by b.volume_id, b.chapter_no, a.full_name asc;
        ";
        return self::getDb()->createCommand($sql)->queryAll();
    }
}