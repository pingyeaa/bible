<?php

namespace frontend\controllers;

use common\models\Scriptures;
use common\models\User;
use common\models\Volume;
use yii\web\Controller;

class WeChatController extends Controller
{

    protected $app_id = 'wxae67eaeeb26d373a';
    protected $app_secret = 'ef596cee9d5cf302f80ebc1d4b79fd25';

    public function beforeAction($action)
    {
        session_start();
        return true;
    }

    /**
     * 获取所有书卷
     */
    public function actionVolume()
    {
        try {
            $volume = new Volume();
            $list = $volume->find()->orderBy('id ASC')->all();
            $data = [];
            foreach($list as $info) {
                $data[] = [
                    'volume_id' => $info['id'],
                    'short_name' => $info['short_name'],
                    'full_name' => $info['full_name'],
                    'is_new' => $info['is_new'],
                    'chapter_number' => Scriptures::getTotalChapter($info['id']),
                ];
            }
            return $this->code(200, '', $data);
        }catch (\Exception $e) {
            return $this->code(500, $e->getMessage());
        }
    }

    /**
     * 用户提示
     */
    public function actionNotice()
    {
        try {
            return $this->code(200, '', ['notice' => \yii::$app->params['wechat_notice']]);
        }catch (\Exception $e) {
            return $this->code(500, $e->getMessage());
        }
    }

    /**
     * 获取所有书卷
     * @param $volume_id
     * @param $chapter_no
     */
    public function actionLection($volume_id, $chapter_no)
    {
        try {
            $scriptures = new Scriptures();
            $list = $scriptures->find()->where(['volume_id' => $volume_id, 'chapter_no' => $chapter_no])->orderBy('verse_no ASC')->all();
            if(!$list) {
                return $this->code(400, '未找到指定经文');
            }
            $data = [];
            foreach($list as $info) {
                $data[] = [
                    'verse_no' => $info['verse_no'],
                    'lection' => $info['lection'],
                ];
            }
            return $this->code(200, '', $data);
        }catch (\Exception $e) {
            return $this->code(500, $e->getMessage());
        }
    }

    public function actionNewContent($token)
    {
        try {
            $openid = $this->authorization($token);
            if(!$openid) {
                return $this->code(426, '`token`已过期');
            }



            return $this->code(200, '', []);
        }catch (\Exception $e) {
            return $this->code(500, $e->getMessage());
        }
    }

    public function actionLogin($code)
    {
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://api.weixin.qq.com/sns/jscode2session?appid=".$this->app_id."&secret=".$this->app_secret."&js_code=$code&grant_type=authorization_code");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $data = curl_exec($ch);
            curl_close($ch);
            $result = \json_decode($data, true);
            if(!isset($result['openid'])) {
                return $this->code(400, '登录失败', ["https://api.weixin.qq.com/sns/jscode2session?appid=".$this->app_id."&secret=".$this->app_secret."&js_code=$code&grant_type=authorization_code"]);
            }

            //生成`token`
            $token = session_id();
            $_SESSION[$token] = sprintf('%s,%s', $result['openid'], $result['session_key']);

            //将`openid`写入`用户表`
            $user_info = User::getByWeChatOpenId($result['openid']);
            if(!$user_info) {
                $user = new User();
                $is = $user->add([
                    'username' => uniqid(),
                    'password' => uniqid(),
                    'created_at' => time(),
                    'updated_at' => time(),
                    'openid' => $result['openid'],
                    'platform_id' => 1,
                    'nation_code' => 86,
                ]);
                if(!$is) {
                    throw new \Exception(\json_encode($user->getErrors()));
                }
            }else {
                User::mod(['last_login_at' => time()], $user_info['id']);
            }
            return $this->code(200, '', ['token' => $token]);
        }catch (\Exception $e) {
            return $this->code(500, $e->getMessage());
        }
    }

    /**
     * 权限验证
     * @param $token
     * @return string openid
     */
    protected function authorization($token)
    {
        if(!isset($_SESSION[$token]) || empty($_SESSION[$token])) {
            return false;
        }
        return explode(',', $_SESSION[$token], true)[0];
    }

    /**
     * @param int $status
     * @param string $message
     * @param array $data
     */
    protected function code($status = 200, $message = '', $data = [])
    {
        $response = \yii::$app->getResponse();
        $output = [
            'code' => $status,
            'message' => $message,
            'data' => $data
        ];
        $response->data = $output;
        \yii::$app->end(0, $response);
    }
}
