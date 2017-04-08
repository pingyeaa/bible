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

    public static function newContent($topic_id, $content_id, $user_id)
    {
        $sql = "
            SELECT A.topic_id, A.content_id, B.topic_name, A.content, A.book_name, A.chapter_no, A.verse_no FROM public.recite_content A 
            INNER JOIN public.recite_topic B ON A.topic_id = B.topic_id 
            LEFT JOIN public.wechat_ignore_record C ON A.content_id = C.content_id AND C.user_id = %d 
            WHERE A.topic_id = %d AND A.content_id > %d AND C.topic_id IS NULL 
            ORDER BY A.content_id ASC LIMIT 1
        ";
        $sql = sprintf($sql, $user_id, $topic_id, $content_id);
        return self::getDb()->createCommand($sql)->queryOne();
    }

    /**
     * 查询某主题下的所有内容数量
     * @param $topic_id
     * @return mixed
     */
    public static function countContent($topic_id)
    {
        $sql = "SELECT COUNT(content_id) AS total FROM public.recite_content WHERE topic_id = $topic_id";
        return self::getDb()->createCommand($sql)->queryOne()['total'];
    }
}