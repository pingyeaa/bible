<?php

namespace common\models;

use yii\db\ActiveRecord;

class WechatReviewRecord extends ActiveRecord
{
    public function table()
    {
        return 'public.wechat_review_record';
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


}