<?php

namespace common\models;

use yii\base\Exception;
use yii\db\ActiveRecord;

class IntercessionComments extends ActiveRecord
{
    public function table()
    {
        return 'public.intercession_comments';
    }

    public function rules()
    {
        return [
            [['intercession_id', 'user_id', 'comment_by_id', 'content', 'praise_number', 'ip', 'created_at', 'updated_at'], 'required']
        ];
    }

    public function add($data)
    {
        $this->isNewRecord = true;
        $this->attributes = $data;
        if(!$this->save())
            throw new Exception(json_encode($this->getErrors()));
        return true;
    }

    public static function getAllByIntercessionId($intercessionId)
    {
        return self::find()->where(['intercession_id' => $intercessionId])->orderBy('praise_number desc')->all();
    }

    public static function findWithCommentId($commentId)
    {
        return self::find()->where(['id' => $commentId])->one();
    }

    /**
     * 递增点赞数量
     * @param $commentId
     * @return bool
     * @throws Exception
     */
    public function increasePraiseNumber($commentId)
    {
        $sql = "update public.intercession_comments set praise_number = praise_number + 1 where id = %d";
        $sql = sprintf($sql, $commentId);
        $result = $this->getDb()->createCommand($sql)->execute();
        if(!$result) {
            throw new Exception(json_encode($this->getErrors()));
        }
        return true;
    }

    /**
     * 递减点赞数量
     * @param $commentId
     * @return bool
     * @throws Exception
     */
    public function decreasePraiseNumber($commentId)
    {
        $sql = "update public.intercession_comments set praise_number = praise_number - 1 where id = %d";
        $sql = sprintf($sql, $commentId);
        $result = $this->getDb()->createCommand($sql)->execute();
        if(!$result) {
            throw new Exception(json_encode($this->getErrors()));
        }
        return true;
    }
}