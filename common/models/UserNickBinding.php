<?php

namespace common\models;

use yii\db\ActiveRecord;

class UserNickBinding extends ActiveRecord
{
    public function table()
    {
        return 'public.user_nick_binding';
    }

    public function rules()
    {
        return [
            [['user_id', 'nick_list_id', 'create_at'], 'required']
        ];
    }

    public function add($data)
    {
        $this->isNewRecord = true;
        $this->attributes = $data;
        return $this->save();
    }

    /**
     * 根据用户查用户标识信息
     * @param $userId
     * @return array|null|ActiveRecord
     */
    public static function findNickInfoByUserId($userId)
    {
        return self::find()->innerJoinWith('nickList')
                            ->where(['user_id' => $userId])
                            ->one();
    }

    public function getNickList()
    {
        return $this->hasOne(NickList::className(), ['id' => 'nick_list_id']);
    }
}