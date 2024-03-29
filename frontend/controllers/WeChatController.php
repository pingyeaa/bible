<?php

namespace frontend\controllers;

use common\models\Annotation;
use common\models\ApiLog;
use common\models\Friends;
use common\models\MessageCenter;
use common\models\ReciteContent;
use common\models\ReciteRecord;
use common\models\ReciteTopic;
use common\models\Scriptures;
use common\models\ScripturesTime;
use common\models\User;
use common\models\Volume;
use common\models\WechatIgnoreRecord;
use common\models\WechatReciteRecord;
use common\models\WechatReviewRecord;
use yii\swiftmailer\Message;
use yii\web\Controller;

class WeChatController extends Controller
{
    protected $startMemory;
    protected $startTime;
    protected $app_id = 'wxae67eaeeb26d373a';
    protected $app_secret = 'ef596cee9d5cf302f80ebc1d4b79fd25';
    protected $gz_app_id = 'wx48681fe10962eb3b';
    protected $gz_app_secret = 'f8bffcacc135dc1a17521b943204a0fb';
    protected $user_id;
    protected $open_id;

    public function beforeAction($action)
    {
        parent::beforeAction($action);
        session_start();

        //初始化内存、时间，用于计算内存消耗
        $this->startMemory = memory_get_usage();
        $this->startTime = microtime(true);

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
     * @param string $nickname
     * @param string $portrait
     * @return mixed
     */
    public function actionLogin($code, $encrypted_data = '', $iv = '', $nickname = '', $portrait = '')
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
                    'nickname' => $nickname,
                    'portrait' => $portrait,
                ];
                if($union_id) { $data['union_id'] = $union_id; }
                $is = $user->add($data);
                if(!$is) {
                    throw new \Exception(\json_encode($user->getErrors()));
                }
                $user_id = $is;
            }else {
                $data = [];
                $data['last_login_at'] = time();
                if($user_info['nickname'] != $nickname) {
                    $data['nickname'] = $nickname;
                }
                if($user_info['portrait'] != $portrait) {
                    $data['portrait'] = $portrait;
                }
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

            return $this->code(200, '', ['token' => $token, 'invitation_code' => $user_id, 'player' => ['show' => \yii::$app->params['audio_hidden']]]);
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
     * 邀请
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
                return $this->code(200, '不能加自己为好友，已忽略');
            }

            //点击邀请码需要互加好友
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
            $friends = new Friends();
            $friendInfo = Friends::findByFriendIdAndUserId($this->user_id, (int)$invitation_code);
            if(!$friendInfo) {
                $is = $friends->add([
                    'user_id' => $invitation_code,
                    'friend_user_id' => $this->user_id,
                    'created_at' => time(),
                    'updated_at' => time(),
                ]);
                if(!$is) {
                    throw new \Exception(json_encode($friends->getErrors()));
                }
            }
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
     * 圣经音频信息获取
     */
    public function actionBibleAudio($token, $volume_id, $chapter_no)
    {
        try {
            $openid = $this->authorization($token);
            if(!$openid) {
                return $this->code(426, '`token`已过期');
            }

            $info = ScripturesTime::find()->where(['volume_id' => $volume_id, 'chapter_no' => $chapter_no])->one();
            if(!$info) {
                return $this->code(451, '未找到该经文的音频时间', []);
            }

            $data = [
//                'path' => '/path/以弗所书第1章.mp3',
                'second' => json_decode(str_replace("\n", "", $info['seconds']))
            ];

            return $this->code(200, '', $data);

        }catch (\Exception $e) {
            return $this->code(500, $e->getMessage());
        }
    }

    /**
     * 今天已经完成背诵的朋友
     */
    public function actionTodayRecitedFriends($token)
    {
        try {
            $openid = $this->authorization($token);
            if(!$openid) {
                return $this->code(426, '`token`已过期');
            }

            $friends = Friends::findTodayRecitedFriends($this->user_id);
            if(!$friends) {
                return $this->code(200, '', []);
            }
            $data = [];
            foreach($friends as $info) {
                $user_info = User::find()->where(['id' => $info['friend_user_id']])->one();
                $data[] = [
                    'nickname' => $user_info['nickname'],
                    'portrait' => $user_info['portrait'],
                    'time' => date('Y-m-d H:i:s', $info['created_at']),
                ];
            }

            return $this->code(200, '', $data);

        }catch (\Exception $e) {
            return $this->code(500, $e->getMessage());
        }
    }

    /**
     * 好友列表
     */
    public function actionFriends($token)
    {
        try {
            $openid = $this->authorization($token);
            if(!$openid) {
                return $this->code(426, '`token`已过期');
            }

            $list = Friends::listByRecitedDays($this->user_id, 1, 20);
            $data = [];
            foreach($list as $info) {
                $user_info = User::find()->where(['id' => $info['friend_user_id']])->one();
                $recite_info = Friends::todayRecitedInfo($info['friend_user_id']);
                $data[] = [
                    'user_id' => $user_info['id'],
                    'nickname' => (String)$user_info['nickname'],
                    'portrait' => (String)$user_info['portrait'],
                    'is_recited' => $recite_info ? 1 : 0,
                    'recited_time' => $recite_info ? date('Y-m-d H:i:s', $recite_info['created_at']) : '',
                    'topic_id' => $recite_info ? $recite_info['topic_id'] : 0,
                    'days' => $info['total'],
                ];
            }

            return $this->code(200, '', $data);

        }catch (\Exception $e) {
            return $this->code(500, $e->getMessage());
        }
    }

    /**
     * 搜索用户
     * @param $token
     * @param $user_id
     */
    public function actionSearchUser($token, $user_id)
    {
        try {
            $openid = $this->authorization($token);
            if(!$openid) {
                return $this->code(426, '`token`已过期');
            }

            $user_info = User::find()->where(['id' => $user_id])->one();
            if(!$user_info) {
                return $this->code(451, '未找到该用户');
            }

            $friend_info = Friends::find()->where(['user_id' => $this->user_id, 'friend_user_id' => $user_id])->one();

            return $this->code(200, '', ['user_id' => $user_info['id'], 'nickname' => $user_info['nickname'], 'portrait' => (String)$user_info['portrait'], 'is_friend' => $friend_info ? 1 : 0]);

        }catch (\Exception $e) {
            return $this->code(500, $e->getMessage());
        }
    }

    /**
     * 关注
     * @param $token
     * @param $user_id
     */
    public function actionFollow($token, $user_id)
    {
        try {
            $openid = $this->authorization($token);
            if(!$openid) {
                return $this->code(426, '`token`已过期');
            }

            //检测是否已经关注
            //未关注则添加关注
            $friend_info = Friends::find()->where(['user_id' => $this->user_id, 'friend_user_id' => $user_id])->one();
            if($friend_info) {
                return $this->code(451, '已经关注对方', []);
            }
            $friends = new Friends();
            $is = $friends->add([
                'user_id' => $this->user_id,
                'friend_user_id' => $user_id,
                'created_at' => time(),
                'updated_at' => time(),
            ]);
            if(!$is) {
                throw new \Exception(\json_encode($friends->getErrors()));
            }

            $user_info = User::find()->where(['id' => $this->user_id])->one();

            //添加到消息中心
            $message = new MessageCenter();
            $message->add([
                'to_user' => $user_id,
                'msg_content' => sprintf('%s关注了你，你也可以试试关注Ta', $user_info['nickname']),
                'msg_type' => 2,    //1-评论通知；2-关注通知；3-点赞通知；4-系统通知
                'created_time' => date('Y-m-d H:i:s'),
            ]);

            return $this->code(200, '', []);

        }catch (\Exception $e) {
            return $this->code(500, $e->getMessage());
        }
    }

    /**
     * 获取站内消息
     * @param $token
     */
    public function actionMessageCenter($token, $page)
    {
        try {
            $openid = $this->authorization($token);
            if(!$openid) {
                return $this->code(426, '`token`已过期');
            }

            $message_list = MessageCenter::find()
                ->where(['to_user' => $this->user_id])
                ->orderBy('id desc')
                ->limit(20)
                ->offset(($page - 1)*20)
                ->all();
            if(!$message_list) {
                return $this->code(200, '', []);
            }
            $data = [];
            foreach($message_list as $message) {
                $data[] = [
                    'id' => $message['id'],
                    'msg_content' => $message['msg_content'],
                    'msg_type' => $message['msg_type'],
                    'created_time' => $message['created_time'],
                ];
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
        //$this->log($response);
        \yii::$app->end(0, $response);
    }

    protected function log($response)
    {
        $log = new ApiLog();
        $log->add([
            'route' => $this->route,
            'request_type' => \yii::$app->request->method,
            'url' => \yii::$app->request->absoluteUrl,
            'params' => urldecode(http_build_query($_REQUEST)),
            'status' => $response->statusCode,
            'response' => json_encode($response->data, JSON_UNESCAPED_UNICODE),
            'ip' => \yii::$app->request->getUserIP(),
            'created_at' => time(),
            'memory' => (memory_get_usage() - $this->startMemory) / 1000,
            'response_time' => sprintf('%.2f', (microtime(true) - $this->startTime)),
        ]);
    }
}
