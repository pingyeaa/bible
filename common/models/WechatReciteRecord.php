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

    public static function currentContent($user_id, $topic_id)
    {
        return self::find()->where(['user_id' => $user_id, 'topic_id' => $topic_id])->orderBy('id desc')->one();
    }
}