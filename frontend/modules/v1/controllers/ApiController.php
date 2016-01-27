<?php

namespace app\modules\v1\controllers;

use yii;
use common\models\NickList;
use common\models\Portrait;
use common\models\SmsRegisterBinding;
use common\models\User;
use common\models\UserNickBinding;
use yii\web\Controller;
use yii\base\Exception;

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

    public function actionUserLogin($username)
    {
        try{
            if(123 == $username) throw new yii\base\Exception('用户名为空');


        }catch (yii\base\Exception $e){
            echo $e->getMessage();
        }
//        yii::$app->qiniu->upload('/home/enoch/图片/test.png', time());
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

            //验证用户是否已存在
            $user = new User();
            $result = $user->isExists($nation_code, $phone);
            if($result)
                $this->code(452, '已经注册');

            //同步账号到腾讯云
            $result = yii::$app->tencent->accountImport(sprintf('%s-%s', $nation_code, $phone));
            if(0 != $result['ErrorCode']) throw new Exception('腾讯云同步错误');

            //开启事务
            $trans = yii::$app->db->beginTransaction();

            //添加新用户
            $userId = $user->add([
                'nation_code' => $nation_code,
                'username' => $phone,
                'password' => $password,
                'created_at' => time(),
                'updated_at' => time()
            ]);
            if(!$userId) {
                $trans->rollBack();
                throw new Exception('用户入库失败');
            }

            //选择用户标识入库
            $nickListObj = new NickList();
            $nickInfo = $nickListObj->getInfoByOrderNo($userId);
            if(!$nickInfo){
                $trans->rollBack();
                throw new Exception('未找到用户标识');
            }
            $userNickObj = new UserNickBinding();
            $result = $userNickObj->add([
                'user_id' => $userId,
                'nick_list_id' => $nickInfo['id'],
                'create_at' => time()
            ]);
            if(!$result) {
                $trans->rollBack();
                throw new Exception('nick_id入库失败');
            }
            $trans->commit();
            $this->code(200, 'ok', [
                'user_id' => $userId,
                'nation_code' => $nation_code,
                'phone' => $phone,
                'nickname' => '',
                'nick_id' => $nickInfo['nick_id'],
                'gender' => 0,
                'birthday' => '',
                'believe_date' => '',
            ]);
        }catch (Exception $e) {
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
            if($resultArray['result'] != 0) throw new Exception('短信发送失败');

            //记录短信
            $smsBinding = new SmsRegisterBinding();
            $result = $smsBinding->add([
                'nation_code' => $nation_code,
                'phone' => $phone,
                'code' => $code,
                'status' => 1,
                'create_at' => time(),
            ]);
            if(!$result) throw new Exception('smsRegisterBinding save error');

            $this->code(200, 'OK');

        }catch (yii\base\Exception $e){
            $this->code(500, $e->getMessage());
        }
    }

    /**
     * 上传用户头像
     * @param int $user_id 用户id
     */
//    public function actionUploadPortrait($user_id)
//    {
//        try {
//            $fileName = md5(time() . uniqid());
//            $filePath = sprintf('%s/resources/upload/%s.jpg', yii::$app->basePath, $fileName);
//
//            //获取文件流并保存
//            $upload = new yii\web\UploadedFile();
//            $instance = $upload->getInstanceByName('portrait');
//            if(null == $instance) {
//                $this->code(450, '未收到图片流');
//            }
//            $is = $instance->saveAs($filePath, true);
//            if(!$is) throw new Exception('图片上传失败');
//
//            //图片同步七牛
//            $is = yii::$app->qiniu->upload($filePath, $fileName);
//            if(!$is) throw new Exception(yii::$app->qiniu->getError());
//
//            //图片入库
//            $portrait = new Portrait();
//            $is = $portrait->add([
//                'user_id' => $user_id,
//                'portrait_name' => $fileName,
//                'created_at' => time(),
//            ]);
//            if(!$is) throw new Exception('图片入库失败');
//
//            //删除临时文件
//            $is = unlink($filePath);
//            if(!$is) throw new Exception('临时文件删除失败');
//
//            $this->code(200, 'ok', ['url' => yii::$app->qiniu->getDomain() . '/' . $fileName]);
//
//        }catch (Exception $e) {
//            $this->code(500, $e->getMessage());
//        }
//    }

    /**
     * 获取七牛上传凭证
     * @param int $user_id
     */
    public function actionQiniuToken($user_id)
    {
        try {
            //生成文件名
            $fileName = md5(time() . uniqid());

            //生成token
            $token = yii::$app->qiniu->generateToken(['callbackUrl' => 'http://119.29.108.48/bible/frontend/web/index.php/v1/callback/qiniu', 'callbackBody' => "key=$fileName", 'saveKey' => $fileName]);
            if(!$token) throw new Exception('token 获取失败');

            $this->code(200, 'ok', ['token' => $token]);

        }catch (Exception $e) {
            $this->code(500, $e->getMessage());
        }
    }

    public function actionUserData($user_id, $nick_name, $gender, $birthday, $believe_date)
    {
        try{
            
        }catch (yii\base\Exception $e) {
            $this->code(500, $e->getMessage());
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
                $message,
            ];
        }
        yii::$app->end(0, $response);
    }
}
