<?php

namespace app\modules\v1\controllers;

use common\models\SmsRegisterBinding;
use common\models\User;
use yii;
use yii\web\Controller;

class ApiController extends Controller
{
    public function beforeAction($action)
    {
        parent::beforeAction($action);

        //验证签名
        if(in_array(yii::$app->request->getUserIP(), yii::$app->params['WithoutVerifyIP'])){
            return true;
        }
        $sign = isset($_REQUEST['sign']) ? $_REQUEST['sign'] : null;
        if(!$sign)
            $this->code(412, '签名错误');
        unset($_REQUEST['sign']);
        $secretKey = yii::$app->params['Authorization']['sign']['secret_key'];
        if(!yii::$app->sign->validate($_REQUEST, $sign, $secretKey))
            $this->code(412, '签名错误');

        //验证时间戳
        $timestamp = isset($_REQUEST['timestamp']) ? $_REQUEST['timestamp'] : null;
        if(!$timestamp)
            $this->code(406, '请求已过期');


        return true;
    }

    public function actionUserLogin()
    {

        $response = yii::$app->getResponse();
        $response->data = [
            'code' => 2,
            'data' => [
                'user_id' => 1,
                'user_name' => 'Enoch',
                'sex' => 1,
            ],
        ];
        return $response;
    }

    /**
     * 用户注册
     * @param $nation_code
     * @param $phone
     * @param $sms_code
     * @param $password
     */
    public function actionUserRegister($nation_code, $phone, $sms_code, $password)
    {
        try{
            $sms = new SmsRegisterBinding();
            $result = $sms->validateSmsCode($nation_code, $phone, $sms_code);
            if(!$result)
                $this->code(450, '验证码不存在或已过期');
            $result = yii::$app->tencent->registerAccount(sprintf('%s-%s', $nation_code, $phone), $password);
            var_dump($result);exit;
        }catch (yii\base\Exception $e) {

        }
    }

    /**
     * 获取注册验证码
     * @param　int $phone 手机号
     * @param int $nation_code　国家码
     */
    public function actionRegisterCode($phone, $nation_code = 86)
    {
        try{
            if(User::findByUsernameAndNationCode($phone, $nation_code))
                $this->code(450, '用户已经注册');

            //发短信
            $code = rand(100000, 999999);
            $resultArray = yii::$app->tencent->sendSMS($phone, sprintf('【活石APP】%s为您的登录验证码，请于30分钟内填写。如非本人操作，请忽略本短信。', $code), "86");
            if($resultArray['result'] != 0)
                $this->code(451, '短信发送失败');

            //记录短信
            $smsBinding = new SmsRegisterBinding();
            $result = $smsBinding->add([
                'nation_code' => $nation_code,
                'phone' => $phone,
                'code' => $code,
                'status' => 1,
                'create_at' => time(),
            ]);
            if(!$result)
                $this->code(452, '其他错误');

            $this->code(200, 'OK');
        }catch (yii\base\Exception $e){
            echo $e->getMessage();
        }
    }

    protected function code($status = 200, $message = '', $data = [])
    {
        $response = yii::$app->getResponse();
        $response->setStatusCode($status);
        $response->data = [
            'message' => $message,
            'data' => $data
        ];
        yii::$app->end(0, $response);
    }
}
