<?php

namespace common\models;

use yii\db\ActiveRecord;

class Annotation extends ActiveRecord
{
    public function table()
    {
        return 'public.annotation';
    }

    public function rules()
    {
        return [

        ];
    }

    /**
     * 查找经文注释
     * @param $book_id
     * @param $chapter_no
     * @param $verse_no
     * @return array|null|ActiveRecord
     */
    public static function findByBookId($book_id, $chapter_no, $verse_no)
    {
        return self::find()->where(['bookId' => $book_id, 'chapterNo' => $chapter_no, 'sectionNo' => $verse_no])->one();
    }
}