<?php

namespace common\models;

use yii\db\ActiveRecord;

class IntercessionCommentPraise extends ActiveRecord
{
    public function table()
    {
        return 'public.intercession_comment_praise';
    }

    public function rules()
    {
        return [
            [['user_id', 'comment_id', 'praise_user_id', 'ip', 'created_at', 'updated_at'], 'required']
        ];
    }

    public function add($data)
    {
        $this->isNewRecord = true;
        $this->attributes = $data;
        return $this->save();
    }

    /**
     * 获取点赞数量
     * @param $commentId
     * @return array|null|ActiveRecord
     */
    public static function getPraiseNumber($commentId)
    {
        $info = self::find()->select('count(id) as total')->where(['comment_id' => $commentId])->all();
        return isset($info['total']) ? $info['total'] : 0;
    }

    /**
     * 取消点赞
     * @param $commentId
     * @param $userId
     * @return int
     */
    public static function cancel($commentId, $userId)
    {
        return self::deleteAll(['comment_id' => $commentId, 'praise_user_id' => $userId]);
    }

    /**
     * @param $commentId
     * @return array|null|ActiveRecord
     */
    public static function findWithCommentId($commentId, $userId)
    {
        return self::find()->where(['comment_id' => $commentId, 'praise_user_id' => $userId])->one();
    }
}