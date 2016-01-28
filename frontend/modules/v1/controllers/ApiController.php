<?php

namespace app\modules\v1\controllers;

use common\models\ApiLog;
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
    protected $startMemory;
    protected $startTime;

    public function beforeAction($action)
    {
        parent::beforeAction($action);

        //初始化内存、时间，用于计算内存消耗
        $this->startMemory = memory_get_usage();
        $this->startTime = microtime(true);

        //验证签名
        //在Module中声明不进行基本验证的也不需签名
        $behavior = $this->module->getBehavior('authenticator');
        if(!isset($behavior->except)) {
            return true;
        }
        $except = $behavior->except;
        $route = sprintf('%s/%s', yii::$app->controller->id, $this->action->id);
        if(in_array($route, $except)){
            return true;
        }

        //指定ip不需签名
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
            $this->code(500, $e->getMessage());
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
                'avatar' => '',
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
    public function actionUploadPortrait($user_id)
    {
        try {
            $fileName = md5(time() . uniqid());
            $filePath = sprintf('%s/resources/upload/%s.jpg', yii::$app->basePath, $fileName);

            //获取文件流并保存
            $upload = new yii\web\UploadedFile();
            $instance = $upload->getInstanceByName('portrait');
            if(null == $instance) {
                $this->code(450, '未收到图片流');
            }
            $is = $instance->saveAs($filePath, true);
            if(!$is) throw new Exception('图片上传失败');

            //图片同步七牛
            $qiniuObj = yii::$app->qiniu;
            $is = $qiniuObj->upload($filePath, null, ['callbackUrl' => $qiniuObj->getCallbackUrl(), 'callbackBody' => "key=$fileName&user_id=1", 'saveKey' => $fileName]);
            if(!$is) throw new Exception(yii::$app->qiniu->getError());

            //图片入库
//            $portrait = new Portrait();
//            $is = $portrait->add([
//                'user_id' => $user_id,
//                'portrait_name' => $fileName,
//                'created_at' => time(),
//            ]);
//            if(!$is) throw new Exception('图片入库失败');

            //删除临时文件
            $is = unlink($filePath);
            if(!$is) throw new Exception('临时文件删除失败');

            $this->code(200, 'ok', ['url' => yii::$app->qiniu->getDomain() . '/' . $fileName]);

        }catch (Exception $e) {
            $this->code(500, $e->getMessage());
        }
    }

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
            $qiniuObj = yii::$app->qiniu;
            $token = $qiniuObj->generateToken(['callbackUrl' => $qiniuObj->getCallbackUrl(), 'callbackBody' => "key=$fileName&user_id=$user_id", 'saveKey' => $fileName]);
            if(!$token) throw new Exception('token 获取失败');

            $this->code(200, 'ok', ['token' => $token]);

        }catch (Exception $e) {
            $this->code(500, $e->getMessage());
        }
    }

    /**
     * 七牛头像上传回调
     * @param $user_id
     * @param string $key 文件名称
     */
    public function actionQiniuCallback($user_id, $key)
    {
        try{
            //验证是否为七牛回调
            $contentType = $_SERVER['HTTP_CONTENT_TYPE'];
            $authorization = $_SERVER['HTTP_AUTHORIZATION'];
            $url = yii::$app->qiniu->getCallbackUrl();
            $body = http_build_query($_POST);
            $is = yii::$app->qiniu->verifyCallback($contentType, $authorization, $url, $body);
            if(!$is) {
                yii::info('请求不合法', 'qiniu-callback');
                $this->code(450, '请求不合法');
            }

            //图片入库
            $portrait = new Portrait();
            $is = $portrait->add([
               'user_id' => $user_id,
               'portrait_name' => $key,
               'created_at' => time(),
            ]);
            if(!$is) throw new Exception(var_export($portrait->getErrors(), true));
            $this->code(200, 'ok', [
                'avatar' => sprintf('%s/%s', yii::$app->qiniu->getDomain(), $key),
            ]);

        }catch (Exception $e) {
            yii::info($e->getMessage(), 'qiniu-callback');
            $this->code(500, $e->getMessage());
        }
    }

    /**
     * 完善用户资料
     * @param $user_id
     * @param $nick_name
     * @param $gender
     * @param $birthday
     * @param $believe_date
     */
    public function actionUserData($user_id, $nick_name, $gender, $birthday, $believe_date)
    {
        try{
            if(1 != $gender && 0 != $gender) {
                $this->code(450, '`gender`错误');
            }

            //检查是否未注册
            $userInfo = User::findIdentity($user_id);
            if(!$userInfo) {
                $this->code(451, '账号未注册');
            }

            //修改信息
            $is = User::mod([
                'nickname' => $nick_name,
                'gender' => $gender,
                'birthday' => $birthday,
                'believe_date' => $believe_date,
                'updated_at' => time(),
            ], $userInfo['id']);
            if(!$is) throw new Exception('用户资料修改失败');

            //返回用户信息
            //获取头像
            $portraitInfo = Portrait::findByUserId($user_id);
            $avatar = $portraitInfo ? yii::$app->qiniu->getDomain() . '/' . $portraitInfo['portrait_name'] : '';

            //获取用户标识
            $nickInfo = UserNickBinding::findNickInfoByUserId($user_id);
            if(!$nickInfo) throw new Exception('未找到用户标识');

            //返回用户数据
            $this->code(200, 'ok', [
                'user_id' => $user_id,
                'nation_code' => $userInfo['nation_code'],
                'avatar' => $avatar,
                'phone' => $userInfo['username'],
                'nickname' => $nick_name,
                'nick_id' => $nickInfo['nickList']['nick_id'],
                'gender' => (int)$gender,
                'birthday' => date('Y-m-d', strtotime($birthday)),
                'believe_date' => date('Y-m-d', strtotime($believe_date)),
            ]);

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
        $this->log($response);
        yii::$app->end(0, $response);
    }

    protected function log($response)
    {
        $log = new ApiLog();
        $log->add([
            'route' => $this->route,
            'request_type' => yii::$app->request->method,
            'url' => yii::$app->request->absoluteUrl,
            'params' => http_build_query($_REQUEST),
            'status' => $response->statusCode,
            'response' => json_encode($response->data, JSON_UNESCAPED_UNICODE),
            'ip' => yii::$app->request->getUserIP(),
            'created_at' => time(),
            'memory' => (memory_get_usage() - $this->startMemory) / 1000,
            'response_time' => sprintf('%.2f', (microtime(true) - $this->startTime)),
        ]);
    }
}
