<?php

namespace common\models;

use yii\db\ActiveRecord;

class SmsRegisterBinding extends ActiveRecord
{
    public function table()
    {
        return 'public.sms_register_binding';
    }

    public function rules()
    {
        return [
            [['nation_code', 'phone', 'code', 'status', 'create_at'], 'required']
        ];
    }

    public function add($data)
    {
        $this->isNewRecord = true;
        $this->attributes = $data;
        return $this->save();
    }

    /**
     * 检测短信验证码是否正确
     * @param string $nationCode　国家码
     * @param string $phone　电话
     * @param string $smsCode　短信验证码
     * @param integer $expireTime　过期时间/s
     * @return bool
     */
    public function validateSmsCode($nationCode, $phone, $smsCode, $expireTime = 30)
    {
        return $this->find()->where('nation_code = :nation_code and phone = :phone and code = :code and create_at >= :validTime', ['nation_code' => $nationCode, 'phone' => $phone, 'code' => $smsCode, 'validTime' => time() - $expireTime])->one();
    }
}