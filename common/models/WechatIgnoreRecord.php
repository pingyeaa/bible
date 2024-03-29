<?php

namespace common\models;

use yii\db\ActiveRecord;

class WechatIgnoreRecord extends ActiveRecord
{
    public function table()
    {
        return 'public.wechat_ignore_record';
    }

    public function rules()
    {
        return [
            [['user_id', 'topic_id', 'created_at', 'content_id'], 'required'],
            [['id'], 'safe'],
        ];
    }

    public function add($data)
    {
        $this->isNewRecord = true;
        $this->attributes = $data;
        return $this->save();
    }

    /**
     * 查看是否已经忽略了该经文
     * @param $user_id
     * @param $topic_id
     * @param $content_id
     * @return array|null|ActiveRecord
     */
    public function findIgnored($user_id, $topic_id, $content_id)
    {
        return $this->find()->where(['topic_id' => $topic_id, 'content_id' => $content_id, 'user_id' => $user_id])->one();
    }

    /**
     * 查询用户某主题忽略数量
     * @param $user_id
     * @param $topic_id
     * @return array|bool
     */
    public static function countIgnoredContent($user_id, $topic_id)
    {
        $sql = "SELECT COUNT(content_id) AS total FROM public.wechat_ignore_record WHERE user_id = $user_id AND topic_id = $topic_id";
        return self::getDb()->createCommand($sql)->queryOne()['total'];
    }
}