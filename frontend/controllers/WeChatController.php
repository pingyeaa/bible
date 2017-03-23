<?php

namespace frontend\controllers;

use common\models\ReciteContent;
use common\models\ReciteRecord;
use common\models\ReciteTopic;
use common\models\Scriptures;
use common\models\User;
use common\models\Volume;
use common\models\WechatIgnoreRecord;
use common\models\WechatReciteRecord;
use common\models\WechatReviewRecord;
use yii\web\Controller;

class WeChatController extends Controller
{

    protected $app_id = 'wxae67eaeeb26d373a';
    protected $app_secret = 'ef596cee9d5cf302f80ebc1d4b79fd25';
    protected $user_id;
    protected $open_id;

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

    /**
     * 新背诵内容
     * @param $token string 令牌
     * @param $topic_id integer 背诵主题id
     */
    public function actionNewContent($token, $topic_id = 0)
    {
        try {
            $openid = $this->authorization($token);
            if(!$openid) {
                return $this->code(426, '`token`已过期');
            }

            $topic_id = (int)$topic_id;
            if(!$topic_id) {
                $info = WechatReciteRecord::findRecitedByUserId($this->user_id);
                if(!$info) {
                    return $this->code(452, '请选择需要背诵的主题');
                }
                $topic_id = $info['topic_id'];
            }

            //查找该用户最新背诵的内容
            //如果未找到则返回`topic_id`为`1`,`content_id`为`1`的内容
            //如果找到则在原来的`topic_id`/`content_id`基础上加`1`
            //如果`content_id`加满了需要在`topic_id`上累加
            $new_content = WechatReciteRecord::currentContent($this->user_id, $topic_id);
            if(!$new_content) {
                $topic_info = ReciteTopic::findById($topic_id);
                if(!$topic_info) {
                    return $this->code(400, '`topic_id`' . $topic_id . '不存在');
                }
                $content_info = ReciteContent::minContent($topic_id);
                if(!$content_info) {
                    return $this->code(400, '`topic_id`' . $topic_id . '下没有背诵内容');
                }
                $data = [
                    'topic_id' => $topic_info['topic_id'],
                    'topic_name' => $topic_info['topic_name'],
                    'content_id' => $content_info['content_id'],
                    'content' => trim($content_info['content']),
                    'book_name' => $content_info['book_name'],
                    'chapter_no' => $content_info['chapter_no'],
                    'verse_no' => trim($content_info['verse_no']),
                ];
            }else {
                $new_content_info = ReciteContent::newContent($new_content['topic_id'], $new_content['content_id']);
                if(!$new_content_info) {
                    return $this->code(451, '当前主题已经背诵完成');
                }
                $data = [
                    'topic_id' => $new_content_info['topic_id'],
                    'topic_name' => $new_content_info['topic_name'],
                    'content_id' => $new_content_info['content_id'],
                    'content' => trim($new_content_info['content']),
                    'book_name' => $new_content_info['book_name'],
                    'chapter_no' => $new_content_info['chapter_no'],
                    'verse_no' => trim($new_content_info['verse_no']),
                ];
            }

            return $this->code(200, '', $data);
        }catch (\Exception $e) {
            return $this->code(500, $e->getMessage());
        }
    }

    /**
     * 所有可背诵的主题列表
     * @param $token string 令牌
     */
    public function actionTopicList($token)
    {
        try {
            $openid = $this->authorization($token);
            if(!$openid) {
                return $this->code(426, '`token`已过期');
            }

            $data = [];
            $all_topic = ReciteTopic::findAllTopic();
            if(!$all_topic) {
                return $this->code(451, '没有可背诵的主题');
            }
            foreach($all_topic as $topic_info) {

                //查询用户是否背诵过该主题


                $data[] = [
                    'topic_id' => $topic_info['topic_id'],
                    'topic_name' => $topic_info['topic_name'],
                    'content_number' => $topic_info['verse_total'],
                ];
            }

            return $this->code(200, '', $data);
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
     * 今日复习内容
     * @param $token
     */
    public function actionTodayReview($token)
    {
        try {
            $openid = $this->authorization($token);
            if(!$openid) {
                return $this->code(426, '`token`已过期');
            }

            //定义需要复习的规律（天数）
            $review_days = [1, 2, 4, 7, 15];

            //查询今日需要复习的经文
            //如果不存在则返回`451`状态码
            $wechat_recite_record = new WechatReciteRecord();
            $review_list = $wechat_recite_record->contentForReview($this->user_id, $review_days);
            if(!$review_list) {
                return $this->code(451, '暂无可复习经文');
            }

            $data = [];
            $time = strtotime(date('Y-m-d 00:00:00'));
            foreach($review_list as $review_info) {
                $data[] = [
                    'topic_id' => $review_info['topic_id'],
                    'topic_name' => $review_info['topic_name'],
                    'content_id' => $review_info['content_id'],
                    'content' => trim($review_info['content']),
                    'day' => (int)(($time - strtotime(date('Y-m-d 00:00:00', $review_info['created_at']))) / (24*60*60)),
                ];
            }

            return $this->code(200, '', $data);

        }catch (\Exception $e) {
            return $this->code(500, $e->getMessage());
        }
    }

    public function actionCompleteRecite($token, $topic_id, $topic_name, $content_id)
    {
        try {
            $openid = $this->authorization($token);
            if(!$openid) {
                return $this->code(426, '`token`已过期');
            }

            //检测该主题以及内容是否已经被忽略
            $recite = new WechatReciteRecord();
            $info = $recite->findRecited($this->user_id, $topic_id, $content_id);
            if($info) {
                return $this->code(451, '该经文已经背诵');
            }

            //如果没有被忽略就新增忽略内容
            $is = $recite->add([
                'user_id' => $this->user_id,
                'topic_id' => $topic_id,
                'topic_name' => $topic_name,
                'content_id' => $content_id,
                'created_at' => time(),
            ]);
            if(!$is) {
                throw new \Exception(json_encode($recite->getErrors()));
            }

            return $this->code(200, '');

        }catch (\Exception $e) {
            return $this->code(500, $e->getMessage());
        }
    }

    /**
     * 忽略经文
     * @param $token
     * @param $topic_id
     * @param $content_id
     */
    public function actionIgnoreContent($token, $topic_id, $content_id)
    {
        try {
            $openid = $this->authorization($token);
            if(!$openid) {
                return $this->code(426, '`token`已过期');
            }

            //检测该主题以及内容是否已经被忽略
            $ignore = new WechatIgnoreRecord();
            $info = $ignore->findIgnored($this->user_id, $topic_id, $content_id);
            if($info) {
                return $this->code(451, '该经文已被忽略');
            }

            //如果没有被忽略就新增忽略内容
            $is = $ignore->add([
                'user_id' => $this->user_id,
                'topic_id' => $topic_id,
                'content_id' => $content_id,
                'created_at' => time(),
            ]);
            if(!$is) {
                throw new \Exception(json_encode($ignore->getErrors()));
            }

            return $this->code(200, '');

        }catch (\Exception $e) {
            return $this->code(500, $e->getMessage());
        }
    }

    /**
     * 当前背诵进度
     * 需要下发每个主题的背诵进度
     * @param $token
     */
    public function actionReciteProgress($token)
    {
        try {
            $data = [];
            $openid = $this->authorization($token);
            if(!$openid) {
                return $this->code(426, '`token`已过期');
            }

            //查询所有可背诵的主题
            $topic_list = ReciteTopic::findAllTopic();
            foreach($topic_list as $topic_info) {
                //查询该用户在此主题下的背诵进度
                //查询用户忽略经文
                //查询用户已背诵经文
                //查询该主题所有经文
                //（忽略经文+已背诵经文）/主题所有经文
                $recited_number = WechatReciteRecord::countRecitedContent($this->user_id, $topic_info['topic_id']);
                $ignored_number = WechatIgnoreRecord::countIgnoredContent($this->user_id, $topic_info['topic_id']);
                $content_number = ReciteContent::countContent($topic_info['topic_id']);
                $data[] = [
                    'topic_id' => $recited_number,
                    'topic_name' => $topic_info['topic_name'],
                    'recited_number' => $recited_number,
                    'ignored_number' => $ignored_number,
                    'content_number' => $content_number,
                    'percent' => round(($recited_number + $ignored_number) / $content_number * 100) . "%",
                ];
            }

            return $this->code(200, '', $data);

        }catch (\Exception $e) {
            return $this->code(500, $e->getMessage());
        }
    }

    /**
     * 查询是否背诵过
     * @param $token
     */
    public function actionRecited($token)
    {
        try {
            $data = [];
            $openid = $this->authorization($token);
            if(!$openid) {
                return $this->code(426, '`token`已过期');
            }

            $info = WechatReciteRecord::findRecitedByUserId($this->user_id);
            if(!$info) {
                $data[] = [
                    'topic_id' => 0,
                ];
            }

            return $this->code(200, '', [
                'topic_id' => $info['topic_id'],
            ]);

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

        $open_id = explode(',', $_SESSION[$token])[0];

        $user_info = User::getByWeChatOpenId($open_id);
        if(!$user_info) {
            return false;
        }

        $this->open_id = $open_id;
        $this->user_id = $user_info['id'];

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
