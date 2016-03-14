<?php

namespace common\models;

use yii\db\ActiveRecord;

class Intercession extends ActiveRecord
{
    public function table()
    {
        return 'public.intercession';
    }

    public function rules()
    {
        return [
            [['privacy', 'user_id', 'content', 'ip', 'comments', 'intercessions', 'created_at', 'position'], 'required'],
            [['updated_at'], 'safe']
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

    public static function findAllByFriendsId($friendsId)
    {
        $sql = "
            select b.content, b.user_id, c.portrait_name, a.nickname, b.created_at, b.position from public.user a
            inner join public.intercession b on a.id = b.user_id
            left join public.portrait c on a.id = c.user_id
            where a.id in (".$friendsId.") order by b.id desc
        ";
        echo $sql;exit;
        return self::getDb()->createCommand($sql)->queryAll();
    }
}