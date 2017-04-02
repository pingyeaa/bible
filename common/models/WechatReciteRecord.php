<?php

namespace common\models;

use yii\db\ActiveRecord;

class WechatReciteRecord extends ActiveRecord
{
    public function table()
    {
        return 'public.wechat_recite_record';
    }

    public function rules()
    {
        return [
            [['user_id', 'topic_id', 'created_at', 'content_id', 'topic_name'], 'required'],
            [['id'], 'safe'],
        ];
    }

    public function add($data)
    {
        $this->isNewRecord = true;
        $this->attributes = $data;
        return $this->save();
    }

    public static function currentContent($user_id, $topic_id)
    {
        return self::find()->where(['user_id' => $user_id, 'topic_id' => $topic_id])->orderBy('id desc')->one();
    }

    public function contentForReview($user_id, $review_days = [])
    {
        $where_time_sql = "";
        if($review_days) {
            foreach($review_days as $key => $day) {
                $date = date('Y-m-d', strtotime("-$day days"));
                $start_at = strtotime($date . ' 00:00:00');
                $end_at = strtotime($date . ' 24:00:00');
                if(0 == $key) {
                    $where_time_sql .= sprintf(" (A.created_at BETWEEN '%s' AND '%s') ", $start_at, $end_at);
                }else {
                    $where_time_sql .= sprintf(" OR (A.created_at BETWEEN '%s' AND '%s') ", $start_at, $end_at);
                }
            }
        }

        $sql = "
            SELECT A.topic_id, B.topic_name, A.content_id, C.content, A.created_at, C.chapter_no, C.verse_no, C.book_name FROM public.wechat_recite_record A 
            INNER JOIN public.recite_topic B ON A.topic_id = B.topic_id 
            INNER JOIN public.recite_content C ON A.content_id = C.content_id 
            WHERE A.user_id = $user_id AND ($where_time_sql) 
            AND C.content_id NOT IN ( 
                SELECT content_id FROM public.wechat_ignore_record WHERE user_id = $user_id 
            ) AND C.content_id NOT IN (
                SELECT content_id FROM public.wechat_review_record WHERE user_id = $user_id AND (created_at BETWEEN '%s' AND '%s') 
            )
            ORDER BY A.id ASC
        ";
        $sql = sprintf($sql, strtotime(date('Y-m-d') . ' 00:00:00'), strtotime(date('Y-m-d') . ' 23:59:59'));
        return self::getDb()->createCommand($sql)->queryAll();
    }

    /**
     * 查找是否已背诵
     * @param $user_id
     * @param $topic_id
     * @param $content_id
     * @return array|null|ActiveRecord
     */
    public function findRecited($user_id, $topic_id, $content_id)
    {
        return $this->find()->where(['topic_id' => $topic_id, 'content_id' => $content_id, 'user_id' => $user_id])->one();
    }

    /**
     * 查询用户某主题背诵数量
     * @param $user_id
     * @param $topic_id
     * @return array|bool
     */
    public static function countRecitedContent($user_id, $topic_id)
    {
        $sql = "SELECT COUNT(content_id) AS total FROM public.wechat_recite_record WHERE user_id = $user_id AND topic_id = $topic_id";
        return self::getDb()->createCommand($sql)->queryOne()['total'];
    }

    /**
     * 查询用户是否背诵过
     * @param $user_id
     * @return array|null|ActiveRecord
     */
    public static function findRecitedByUserId($user_id)
    {
        return self::find()->where(['user_id' => $user_id])->orderBy('id desc')->one();
    }
}