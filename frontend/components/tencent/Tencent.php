<?php
/**
 * 腾讯云
 * User: enoch
 * Date: 16-1-13
 * Time: 下午1:38
 */

namespace frontend\components\tencent;
use yii;

require_once("TimRestApi.php");

class Tencent extends yii\base\Component
{
    public $sdkAppId;
    public $userSig;
    public $identifier;
    public $appKey;

    protected $api;

    public function init()
    {
        $this->api = new \TimRestAPI();
        $this->api->init($this->sdkAppId, $this->identifier);
        $this->api->set_user_sig($this->userSig);
    }

    /**
     * 短信发送
     * @param $phone 　手机号码
     * @param int $nationCode 　国家码
     * @param $message e.g '1234为您的登录验证码，请于30分钟内填写。如非本人操作，请忽略本短信。'
     * @param int $type 　0-普通短信　1-营销短信
     * @param string $ext 　扩展参数，腾讯会原封不动传回来
     * @return mixed
     */
    public function sendSMS($phone, $message, $nationCode = "86", $type = "0", $ext = '')
    {
        $result = $this->api->sms_send_to_single($nationCode, $phone, $type, $message, md5($this->appKey .$phone), $ext);
        yii::trace('短信发送结果：' . json_encode($result), 'yii\sendSMS');
        return $result;
    }

    /**
     * 托管模式导入账号
     * @param $userName
     * @param $password
     * @param int $IdentifierType 1:手机号(国家码-手机号) 2:邮箱 3:字符串帐号。
     * @return bool
     */
    public function registerAccount($userName, $password, $IdentifierType = 1)
    {
        $result = $this->api->register_account($userName, $IdentifierType, $password);
        return $this->parseData($result);
    }

    /**
     * 处理结果集
     * @param string $data 接口返回结果
     * @return mixed
     */
    protected function parseData($data)
    {
        return json_decode($data);
    }
}