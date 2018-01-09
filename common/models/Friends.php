<?php

namespace common\models;

use yii\db\ActiveRecord;
use yii;

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

    /**
     * 根据用户查找几维内的好友id
     * @param $userId
     * @param $depth
     * @return array
     */
    public static function findFriendsByUserIdAndDepth($userId, $depth)
    {
        $sql = "
            with recursive re as (
                select b.user_id,b.friend_user_id,1 as depth from public.friends b where b.user_id = %d
                union all
                select c.user_id,c.friend_user_id,d.depth+1 as depth from public.friends c inner join re d on c.user_id = d.friend_user_id where d.depth < %d
            )select user_id,friend_user_id,depth from re;
        ";
        $sql = sprintf($sql, $userId, $depth);
        return self::getDb()->createCommand($sql)->queryAll();
    }

    /**
     * 查询某用户的朋友信息
     * @param $userId
     * @return array
     */
    public static function findAllInfoByUserId($userId)
    {
        $sql = "
            SELECT * FROM public.friends a 
            INNER JOIN public.user b ON a.friend_user_id = b.id 
            WHERE a.user_id = %d
        ";
        $sql = sprintf($sql, $userId);
        return self::getDb()->createCommand($sql)->queryAll();
    }

    /**
     * 查询今日已背诵过的用户
     */
    public static function findTodayRecitedFriends($user_id)
    {
        $sql = "
            SELECT a.friend_user_id, b.created_at FROM public.friends a 
            INNER JOIN public.wechat_recite_record b ON a.friend_user_id = b.user_id 
            WHERE a.user_id = %d AND b.created_at BETWEEN '%s' AND '%s' 
            ORDER BY b.created_at ASC 
            LIMIT 10
        ";
        $sql = sprintf($sql, $user_id, strtotime(date('Y-m-d 00:00:00')), strtotime(date('Y-m-d 23:59:59')));
        return self::getDb()->createCommand($sql)->queryAll();
    }

    /**
     * 获取按累计天数背诵从高到低排序的好友列表
     * @param $user_id
     * @return array
     */
    public static function listByRecitedDays($user_id, $page_no, $limit)
    {
        $sql = "
            SELECT COUNT(b.id) AS total, a.friend_user_id FROM public.friends a 
            LEFT JOIN public.wechat_recite_record b ON a.friend_user_id = b.user_id 
            WHERE a.user_id = %d 
            GROUP BY to_char(to_timestamp(b.created_at), 'yyyy-MM-dd'), a.friend_user_id 
            ORDER BY total DESC 
            LIMIT %d OFFSET %d 
        ";
        $sql = sprintf($sql, $user_id, $limit, ($page_no - 1)*$limit);
        return self::getDb()->createCommand($sql)->queryAll();
    }

    /**
     * 查询某人今天背诵情况
     * @param $user_id
     * @return array|bool
     */
    public static function todayRecitedInfo($user_id)
    {
        $sql = "
            SELECT * FROM public.wechat_recite_record 
            WHERE user_id = %d AND created_at BETWEEN '%s' AND '%s' 
            ORDER BY id DESC 
            LIMIT 1 
        ";
        $sql = sprintf($sql, $user_id, strtotime(date('Y-m-d 00:00:00')), strtotime(date('Y-m-d 23:59:59')));
        return self::getDb()->createCommand($sql)->queryOne();
    }
}