<?php

namespace common\models;

use yii\base\Exception;
use yii\db\ActiveRecord;

class SyncContactsRecord extends ActiveRecord
{
    public function table()
    {
        return 'public.sync_contacts_record';
    }

    public function rules()
    {
        return [
            [['user_id', 'created_at'], 'required'],
        ];
    }

    public function add($data)
    {
        $this->isNewRecord = true;
        $this->attributes = $data;
        $is = $this->save();
        if(!$is) {
            throw new Exception(json_encode($this->getErrors()));
        }
        return $is;
    }

    public static function findByUserId($userId)
    {
        return self::find()->where(['user_id' => $userId])->orderBy('id desc')->one();
    }
}