<?php

namespace common\models;

use yii\base\Exception;
use yii\db\ActiveRecord;

class AppVersion extends ActiveRecord
{
    public function table()
    {
        return 'public.app_version';
    }

    public function rules()
    {
        return [
            [['user_id', 'device_type', 'version', 'description', 'created_at', 'updated_at'], 'required'],
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

    /**
     * @param $device_type
     * @return array|null|ActiveRecord
     */
    public static function findLatestVersion($device_type)
    {
        return self::find()->where(['device_type' => $device_type])->orderBy('id desc')->one();
    }
}