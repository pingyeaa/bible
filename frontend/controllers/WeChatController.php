<?php

namespace frontend\controllers;

use common\models\Annotation;
use common\models\Friends;
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
    protected $gz_app_id = 'wx48681fe10962eb3b';
    protected $gz_app_secret = 'f8bffcacc135dc1a17521b943204a0fb';
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

            //统计用户累积背诵天数
            $total = (int)ReciteRecord::countRecitedDays($this->user_id);

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

                //查询注释
                $annotation_string = '';
                $annotation = Annotation::findByBookId($content_info['book_id'], $content_info['chapter_no'], trim($content_info['verse_no']));
                if($annotation) {
                    $annotation_string = trim($annotation['noteText']);
                }

                $data = [
                    'topic_id' => $topic_info['topic_id'],
                    'topic_name' => $topic_info['topic_name'],
                    'content_id' => $content_info['content_id'],
                    'content' => trim($content_info['content']),
                    'book_name' => $content_info['book_name'],
                    'chapter_no' => $content_info['chapter_no'],
                    'verse_no' => trim($content_info['verse_no']),
                    'recited_days' => $total,
                    'percent' => "0%",
                    'annotation' => $annotation_string,
                ];
            }else {
                $new_content_info = ReciteContent::newContent($new_content['topic_id'], $new_content['content_id'], $this->user_id);
                if(!$new_content_info) {
                    return $this->code(451, '当前主题已经背诵完成');
                }

                //查询该用户在此主题下的背诵进度
                //查询用户忽略经文
                //查询用户已背诵经文
                //查询该主题所有经文
                //（忽略经文+已背诵经文）/主题所有经文
                $recited_number = WechatReciteRecord::countRecitedContent($this->user_id, $topic_id);
                $ignored_number = WechatIgnoreRecord::countIgnoredContent($this->user_id, $topic_id);
                $content_number = ReciteContent::countContent($topic_id);
                $percent = round(($recited_number + $ignored_number) / $content_number * 100) . "%";

                //查询注释
                $annotation_string = '';
                $annotation = Annotation::findByBookId($new_content_info['book_id'], $new_content_info['chapter_no'], trim($new_content_info['verse_no']));
                if($annotation) {
                    $annotation_string = trim($annotation['noteText']);
                }

                $data = [
                    'topic_id' => $new_content_info['topic_id'],
                    'topic_name' => $new_content_info['topic_name'],
                    'content_id' => $new_content_info['content_id'],
                    'content' => trim($new_content_info['content']),
                    'book_name' => $new_content_info['book_name'],
                    'chapter_no' => $new_content_info['chapter_no'],
                    'verse_no' => trim($new_content_info['verse_no']),
                    'recited_days' => $total,
                    'percent' => $percent,
                    'annotation' => $annotation_string,
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

    /**
     * @param $code
     * @param string $encrypted_data
     * @param string $iv
     * @return mixed
     */
    public function actionLogin($code, $encrypted_data = '', $iv = '')
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

            //解密出`union_id`
            $union_id = '';
            if($encrypted_data && $iv) {
                $iv = str_replace(' ', '+', urldecode($iv));
                $encrypted_data = str_replace(' ', '+', urldecode($encrypted_data));
                include_once __DIR__."/../components/wechat/wxBizDataCrypt.php";
                $pc = new \WXBizDataCrypt($this->app_id, $result['session_key']);
                $errCode = $pc->decryptData($encrypted_data, $iv, $data);
                if ($errCode == 0) {
                    $union_id = json_decode($data, true)['unionId'];
                } else {
                    throw new \Exception('`encrypt_data`解析失败' . $errCode);
                }
            }

            //将`openid`写入`用户表`
            $user_info = User::getByWeChatOpenId($result['openid']);
            if(!$user_info) {
                $user = new User();
                $data = [
                    'username' => uniqid(),
                    'password' => uniqid(),
                    'created_at' => time(),
                    'updated_at' => time(),
                    'openid' => $result['openid'],
                    'platform_id' => 1,
                    'nation_code' => 86,
                ];
                if($union_id) { $data['union_id'] = $union_id; }
                $is = $user->add($data);
                if(!$is) {
                    throw new \Exception(\json_encode($user->getErrors()));
                }
                $user_id = $is;
            }else {
                $data = ['last_login_at' => time()];
                if($union_id) { $data['union_id'] = $union_id; }
                User::mod($data, $user_info['id']);
                $user_id = $user_info['id'];
            }

            $token = \Yii::$app->redis->get('user_id_' . $user_id);
            if(!$token) {
                $token = uniqid();
                \Yii::$app->redis->set('user_id_' . $user_id, $token, 'EX', 3600);
                \Yii::$app->redis->set($token, $user_id, 'EX', 3600);
            }

            return $this->code(200, '', ['token' => $token, 'invitation_code' => $user_id]);
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

                //查询注释
                $annotation_string = '';
                $annotation = Annotation::findByBookId($review_info['book_id'], $review_info['chapter_no'], trim($review_info['verse_no']));
                if($annotation) {
                    $annotation_string = trim($annotation['noteText']);
                }

                $data[] = [
                    'topic_id' => $review_info['topic_id'],
                    'topic_name' => $review_info['topic_name'],
                    'content_id' => $review_info['content_id'],
                    'content' => trim($review_info['content']),
                    'book_name' => trim($review_info['book_name']),
                    'chapter_no' => trim($review_info['chapter_no']),
                    'verse_no' => trim($review_info['verse_no']),
                    'day' => (int)(($time - strtotime(date('Y-m-d 00:00:00', $review_info['created_at']))) / (24*60*60)),
                    'annotation' => $annotation_string,
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
                'times' => 1,
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
                    'topic_id' => $topic_info['topic_id'],
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
     * 完成复习接口
     * @param $token
     * @param $topic_id
     * @param $content_id
     */
    public function actionCompleteReview($token, $topic_id, $content_id)
    {
        try {
            $openid = $this->authorization($token);
            if(!$openid) {
                return $this->code(426, '`token`已过期');
            }

            //检测该主题以及内容是否已经复习
            $review = new WechatReviewRecord();
            $info = $review->findReviewed($this->user_id, $topic_id, $content_id);
            if($info) {
                $review->autoIncreaseTimes($content_id);
                return $this->code(200, '已增加一次复习次数');
            }

            //如果没有复习就写入到数据库
            $is = $review->add([
                'user_id' => $this->user_id,
                'topic_id' => $topic_id,
                'content_id' => $content_id,
                'created_at' => time(),
                'times' => 1,
            ]);
            if(!$is) {
                throw new \Exception(json_encode($review->getErrors()));
            }

            return $this->code(200, '');

        }catch (\Exception $e) {
            return $this->code(500, $e->getMessage());
        }
    }

    /**
     * 完成复习接口
     * @param $token
     * @param $invitation_code string 邀请码
     */
    public function actionInvitation($token, $invitation_code)
    {
        try {
            $openid = $this->authorization($token);
            if(!$openid) {
                return $this->code(426, '`token`已过期');
            }

            if($this->user_id == $invitation_code) {
                return $this->code(200, '不能关注自己，已忽略');
            }

            //被关注的人是`target_user_id`
            //应该保存在`user_id`字段
            //因为你关注别人你就是别人的朋友了
            $friends = new Friends();
            $friendInfo = Friends::findByFriendIdAndUserId((int)$invitation_code, $this->user_id);
            if(!$friendInfo) {
                $is = $friends->add([
                    'user_id' => $this->user_id,
                    'friend_user_id' => $invitation_code,
                    'created_at' => time(),
                    'updated_at' => time(),
                ]);
                if(!$is) {
                    throw new \Exception(json_encode($friends->getErrors()));
                }
            }
            $this->code(200, '', []);

            return $this->code(200, '');

        }catch (\Exception $e) {
            return $this->code(500, $e->getMessage());
        }
    }

    /**
     * 今日背诵过的经文
     * @param $token
     * @param $page_no
     * @param $limit
     */
    public function actionRecitedToday($token, $page_no, $limit)
    {
        try {
            $openid = $this->authorization($token);
            if(!$openid) {
                return $this->code(426, '`token`已过期');
            }

            $data = [];
            $list = WechatReciteRecord::todayRecited($this->user_id, $page_no, $limit);
            if($list) {
                foreach($list as $info) {

                    //查询经文详细信息
                    $content_info = ReciteContent::findById($info['topic_id'], $info['content_id']);

                    //查询复习次数
                    $wechat_review_record = new WechatReviewRecord();
                    $review_info = $wechat_review_record->findReviewed($this->user_id, $info['topic_id'], $info['content_id']);

                    $data[] = [
                        'topic_id' => $info['topic_id'],
                        'topic_name' => $info['topic_name'],
                        'content_id' => $info['content_id'],
                        'content' => $content_info['content'],
                        'book_name' => $content_info['book_name'],
                        'chapter_no' => $content_info['chapter_no'],
                        'verse_no' => $content_info['verse_no'],
                        'recitation_times' => $review_info ? $review_info['times'] + 1 : 1,
                    ];
                }
            }

            return $this->code(200, '', $data);

        }catch (\Exception $e) {
            return $this->code(500, $e->getMessage());
        }
    }

    /**
     * 已背诵的历史经文
     * @param $token
     * @param $page_no
     * @param $limit
     */
    public function actionRecitedBefore($token, $page_no, $limit)
    {
        try {
            $openid = $this->authorization($token);
            if(!$openid) {
                return $this->code(426, '`token`已过期');
            }

            $data = [];
            $list = WechatReciteRecord::historyRecited($this->user_id, $page_no, $limit);
            if($list) {
                foreach($list as $info) {

                    //查询经文详细信息
                    $content_info = ReciteContent::findById($info['topic_id'], $info['content_id']);

                    //查询复习次数
                    $wechat_review_record = new WechatReviewRecord();
                    $review_info = $wechat_review_record->findReviewed($this->user_id, $info['topic_id'], $info['content_id']);

                    $data[] = [
                        'topic_id' => $info['topic_id'],
                        'topic_name' => $info['topic_name'],
                        'content_id' => $info['content_id'],
                        'content' => $content_info['content'],
                        'book_name' => $content_info['book_name'],
                        'chapter_no' => $content_info['chapter_no'],
                        'verse_no' => $content_info['verse_no'],
                        'recitation_times' => $review_info ? $review_info['times'] + 1 : 1,
                    ];
                }
            }

            return $this->code(200, '', $data);

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
        $this->user_id = \Yii::$app->redis->get($token);
        return $this->user_id;
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
