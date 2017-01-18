<?php

namespace common\models;

use yii\db\ActiveRecord;

class Scriptures extends ActiveRecord
{
    public function table()
    {
        return 'public.scriptures';
    }

    public function rules()
    {
        return [
        ];
    }

    /**
     * @param $volume_id
     * @return array|bool
     */
    public static function getTotalChapter($volume_id)
    {
        $sql = "SELECT COUNT(chapter_no) AS total FROM public.scriptures WHERE volume_id = $volume_id GROUP BY chapter_no";
        return self::getDb()->createCommand($sql)->queryOne()['total'];
    }
}