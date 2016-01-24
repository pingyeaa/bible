<?php

namespace app\modules\v1\controllers;

use common\models\Nick;
use common\models\NickList;
use common\models\SmsRegisterBinding;
use common\models\User;
use common\models\UserNickBinding;
use common\models\Users;
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
//        $nick = new NickList();
//        $nick->generate(130);exit;
//        $result = yii::$app->tencent->registerAccount(sprintf('%s-%s', $nation_code, $phone), $password);
//        echo 1;exit;
//        $this->code(200, null, ['user_id' => 99, 'sex' => 0]);
//        $this->code(450, '手机号码不正确', ['user_id' => 99, 'sex' => 0]);
        return true;
    }

    /**
     * 用户注册
     * @param $nation_code
     * @param $phone
     * @param $sms_code
     * @param $password
     */
    public function actionUserRegister($nation_code = '86', $phone, $sms_code, $password)
    {
        try{
            //验证码正确性
            $sms = new SmsRegisterBinding();
            $result = $sms->validateSmsCode($nation_code, $phone, $sms_code, 1800);
            if(!$result)
                $this->code(450, '验证码不存在或已过期');

            if(strlen($password) < 8 || strlen($password) > 16)
                $this->code(451, '密码长度必须8-16位');

            //验证用户是否已存在
            $user = new User();
            $result = $user->isExists($nation_code, $phone);
            if($result)
                $this->code(452, '已经注册');

            //同步账号到腾讯云
            $result = yii::$app->tencent->accountImport(sprintf('%s-%s', $nation_code, $phone));
            if(0 != $result['ErrorCode'])
                $this->code(453, '腾讯云同步错误');

            //开启事务
            $trans = yii::$app->db->beginTransaction();

            //添加新用户
            $userId = $user->add([
                'nation_code' => $nation_code,
                'username' => $phone,
                'password' => md5($password . yii::$app->params['User']['password']['saltValue']),
                'created_at' => time(),
                'updated_at' => time()
            ]);
            if(!$userId) {
                $trans->rollBack();
                $this->code(453, '用户入库失败');
            }

            //选择用户标识入库
            $nickListObj = new NickList();
            $nickInfo = $nickListObj->getInfoByOrderNo($userId);
            if(!$nickInfo){
                $trans->rollBack();
                $this->code(453, '未找到用户标识');
            }
            $userNickObj = new UserNickBinding();
            $result = $userNickObj->add([
                'user_id' => $userId,
                'nick_list_id' => $nickInfo['id'],
                'create_at' => time()
            ]);
            if(!$result) {
                $trans->rollBack();
                $this->code(453, 'nick_id入库失败');
            }
            $trans->commit();
            $this->code(200);
        }catch (yii\base\Exception $e) {
            $this->code(500, $e->getMessage());
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
        if(200 == $status) {
            $response->data = $data;
        }else {
            $response->data = [
                'message' => $message,
            ];
        }
        yii::$app->end(0, $response);
    }
}
