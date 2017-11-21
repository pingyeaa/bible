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
            [['volume_id', 'chapter_no', 'seconds'], 'required']
        ];
    }

    public function add($volume_id, $chapter_no, $seconds)
    {
        $sql = "INSERT INTO public.scriptures_time (volume_id, chapter_no, seconds) VALUES (%s, %s, '%s')";
        $sql = sprintf($sql, $volume_id, $chapter_no, $seconds);
        return $this->getDb()->createCommand($sql)->execute();
    }
}