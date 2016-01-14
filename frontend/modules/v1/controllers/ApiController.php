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

        $sign = $_REQUEST['sign'];
        unset($_REQUEST['sign']);
        $secretKey = yii::$app->params['Authorization']['sign']['secret_key'];
        if(!yii::$app->sign->validate($_REQUEST, $sign, $secretKey))
            $this->code(412, -1, '签名错误');
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

    public function actionUserRegister()
    {
        try{
//        yii::$app->tencent->sendSMS();
            yii::$app->tencent->registerAccount('86-15989529532', '123456789');
        }catch (yii\base\Exception $e) {

        }
    }

    /**
     * 获取注册验证码
     * @param　int $phone 手机号
     * @param int $nation_code　国家码
     */
    public function actionRegisterCode($phone, $nation_code)
    {
        try{
            if(User::findByUsernameAndNationCode($phone, $nation_code))
                $this->code(200, 1, '用户已经注册');

            //发短信
            $code = rand(100000, 999999);
            $resultArray = yii::$app->tencent->sendSMS($phone, sprintf('【活石APP】%s为您的登录验证码，请于30分钟内填写。如非本人操作，请忽略本短信。', $code), "86");
            if($resultArray['result'] != 0)
                $this->code(200, 2, '短信发送失败');

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
                $this->code(200, 100, '其他错误');

            $this->code(200, 0, 'OK');
        }catch (yii\base\Exception $e){
            echo $e->getMessage();
        }
    }

    protected function code($status = 200, $code = 0, $message = '', $data = [])
    {
        $response = yii::$app->getResponse();
        $response->setStatusCode($status);
        $response->data = [
            'code' => $code,
            'message' => $message,
            'data' => $data
        ];
        yii::$app->end(0, $response);
    }
}
