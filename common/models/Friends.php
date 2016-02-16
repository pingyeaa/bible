<?php

namespace common\models;

use yii\db\ActiveRecord;

class Friends extends ActiveRecord
{
    public function table()
    {
        return 'public.friends';
    }

    public function rules()
    {
        return [
            [['user_id', 'friend_user_id', 'created_at', 'updated_at'], 'required']
        ];
    }

    public function add($data)
    {
        $this->isNewRecord = true;
        $this->attributes = $data;
        return $this->save();
    }

    public static function findByUserId($userId)
    {
        return self::find()->where(['user_id' => $userId])->orderBy('id desc')->one();
    }

    /**
     * 修改数据
     * @param $data
     * @param $userId
     * @return int
     */
    public static function mod($data, $userId)
    {
        return self::updateAll($data, 'user_id = :user_id', ['user_id' => $userId]);
    }

    /**
     * @param $userId
     * @return array|\yii\db\ActiveRecord[]
     */
    public static function findAllByUserId($userId)
    {
        return self::find()->where(['user_id' => $userId])->all();
    }

    /**
     * 根据手机号和国家码查找用户信息
     * @param $nationCode
     * @param $phone
     * @return array|null|\yii\db\ActiveRecord
     */
    public static function findByPhone($nationCode, $phone)
    {
        return self::find()->innerJoinWith('user')
                    ->where(['username' => $phone, 'nation_code' => $nationCode])
                    ->one();
    }

    public function getUser()
    {
        return self::hasOne(User::className(), ['friend_user_id' => 'user_id']);
    }

    /**
     * @param $friendId
     * @param $userId
     * @return array|null|ActiveRecord
     */
    public static function findByFriendIdAndUserId($friendId, $userId)
    {
        return self::find()->where(['friend_user_id' => $friendId, 'user_id' => $userId])->one();
    }

    /**
     * @param $friendIdArray
     * @return $this|static
     */
    public static function findAllByFriendIds($friendIdArray)
    {
        return self::find()->where('user_id in (:user_id)', ['user_id' => implode(',', $friendIdArray)]);
    }
}