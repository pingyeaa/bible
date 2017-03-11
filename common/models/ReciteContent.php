<?php

namespace common\models;

use yii\db\ActiveRecord;

class ReciteContent extends ActiveRecord
{
    public function table()
    {
        return 'public.recite_content';
    }

    public function rules()
    {
        return [
            [['content_id', 'topic_id', 'book_name', 'chapter_no', 'verse_no'], 'required'],
        ];
    }

    public static function findById($topic_id, $content_id)
    {
        return self::find()->where(['content_id' => $content_id, 'topic_id' => $topic_id])->one();
    }

    public static function minContent($topic_id)
    {
        return self::find()->where(['topic_id' => $topic_id])->orderBy('content_id asc')->one();
    }

    public static function newContent($topic_id, $content_id)
    {
        $sql = "
            SELECT A.topic_id, A.content_id, B.topic_name, A.content, A.book_name, A.chapter_no, A.verse_no FROM public.recite_content A 
            INNER JOIN public.recite_topic B ON A.topic_id = B.topic_id 
            WHERE A.topic_id = %d AND A.content_id > %d 
            ORDER BY A.content_id ASC LIMIT 1
        ";
        $sql = sprintf($sql, $topic_id, $content_id);
        return self::getDb()->createCommand($sql)->queryOne();
    }
}