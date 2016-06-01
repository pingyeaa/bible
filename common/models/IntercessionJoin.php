<?php

namespace common\models;

use yii\db\ActiveRecord;

class IntercessionJoin extends ActiveRecord
{
    public function table()
    {
        return 'public.intercession_join';
    }

    public function rules()
    {
        return [
            [['intercession_id', 'user_id', 'intercessors_id', 'ip', 'created_at', 'updated_at'], 'required']
        ];
    }

    public function add($data)
    {
        $this->isNewRecord = true;
        $this->attributes = $data;
        return $this->save();
    }

    public static function getAllByIntercessionId($intercessionId)
    {
        $sql = "
            select a.id,a.nickname from public.user a inner join public.intercession_join b
            on a.id = b.intercessors_id where b.intercession_id = %d
        ";
        $sql = sprintf($sql, $intercessionId);
        return self::getDb()->createCommand($sql)->queryAll();
    }

    /**
     * @param $intercessionId
     * @param $intercessorsId
     * @return $this|static
     */
    public static function findByIntercessionIdAndIntercessorsId($intercessionId, $intercessorsId)
    {
        return self::find()->where(['intercession_id' => $intercessionId, 'intercessors_id' => $intercessorsId])->one();
    }

    /**
     * 获取某人总代祷次数
     * @param $intercessorsId
     * @return array|null|ActiveRecord
     */
    public static function findTotalWithIntercessorsId($intercessorsId)
    {
        $sql = "select count(id) as total from public.intercession_join where intercessors_id = %d";
        $sql = sprintf($sql, $intercessorsId);
        $info = self::getDb()->createCommand($sql)->queryOne();
        return $info['total'];
    }

    public static function findWithIntercessorsIdAndIntercessionId($intercessorsId, $intercessionId)
    {
        return self::findOne(['intercession_id' => $intercessionId, 'intercessors_id' => $intercessorsId]);
    }

    public static function findAllByUserId($userId, $startPage, $pageNo)
    {
        $sql = "
            select * from public.user a
            inner join public.intercession_join b on a.id = b.user_id
            inner join public.intercession c on b.intercession_id = c.id
            where b.intercessors_id = %d order by c.id desc limit %d offset %d
        ";
        $sql = sprintf($sql, $userId, $pageNo, ($startPage - 1) * $pageNo);
        return self::getDb()->createCommand($sql)->queryAll();
    }

    /**
     * 获取某人加入的总代祷次数
     * @param $userId
     * @return array|null|ActiveRecord
     */
    public static function findTotalWithUserId($userId)
    {
        $sql = "select count(id) as total from public.intercession_join where user_id = %d";
        $sql = sprintf($sql, $userId);
        $info = self::getDb()->createCommand($sql)->queryOne();
        return $info['total'];
    }

}