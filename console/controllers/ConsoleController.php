<?php

namespace console\controllers;

use common\models\AskedDaily;
use common\models\Friends;
use common\models\NickList;
use common\models\Scriptures;
use common\models\ScripturesTime;
use common\models\Volume;
use yii;

class ConsoleController extends yii\console\Controller
{
    /**
     * 批量生成用户标识
     * @param integer $number 生成数量
     * @return bool
     */
    public function actionGenerateNick($number)
    {
        $nick = new NickList();
        return $nick->generate($number);
    }

    /**
     * 批量导入`每日一问`
     */
    public function actionImportQuestion()
    {
        $filePath = yii::$app->params['questionExcelPath'];
        $data = yii::$app->excel->import($filePath);
        foreach($data as $k => $v) {
            $title = $v['title'];
            $answer = $v['answer'];
            $url = $v['url'];
            foreach($v as $answerK => $answerV) {
                if(!empty($answerV) && $answerK != 'answer' && $answerK != 'question' && $answerK != 'title' && $answerK != '作者' && $answerK != 'url') {
                    $answer = $answerK . $answerV . "\n" . $answer;
                }
            }
            if($title) {
                $ask = new AskedDaily();
                $ask->add([
                    'title' => $title,
                    'content' => trim($answer),
                    'url' => $url,
                    'created_at' => time(),
                    'updated_at' => time(),
                    'status' => 0,
                ]);
            }
        }
    }

    /**
     * 每日一问问题替换
     */
    public function actionAskedQuestionMark()
    {
        $info = AskedDaily::findNextOne();
        if(!$info) {
            return false;
        }
        AskedDaily::mod([
            'status' => 1,
        ], $info['id']);
    }

    /**
     * 导出音频所需经文文字内容
     */
    public function actionDump()
    {
        $volume_list = Volume::find()->select('id, full_name')->orderBy('id asc')->all();
        foreach($volume_list as $volume) {
            $scriptures_list = Scriptures::find()->where(['volume_id' => $volume['id']])->orderBy('chapter_no asc, verse_no asc')->all();
            foreach($scriptures_list as $scripture) {
                $scripture_file = '/tmp/bible/' . $volume['full_name']. str_pad($scripture['chapter_no'], 2, '0', STR_PAD_LEFT) . '.txt';
                echo sprintf('卷：%s 章：%s 节：%s', $volume['full_name'], $scripture['chapter_no'], $scripture['verse_no']);
                file_put_contents($scripture_file, sprintf("%d:%d %s\n", $scripture['chapter_no'], $scripture['verse_no'], $scripture['lection']), FILE_APPEND);
            }
        }
    }

    /**
     * 转义圣经文件名为数字
     */
    public function actionEscape()
    {
        $volume_list = Volume::volumeList();
        foreach($volume_list as $volume_info) {
            if(!realpath('/mydata/audio/' . $volume_info['volume_id'])) {
                mkdir('/mydata/audio/' . $volume_info['volume_id']);
            }
            $file_name = sprintf('/mydata/audio/%s第%s章.mp3', $volume_info['full_name'], $volume_info['chapter_no']);
            echo '开始处理文件' . $file_name . "\n";
            if(!is_file($file_name)) {
                continue;
            }
            $is = rename($file_name, sprintf('/mydata/audio/%d/%s.mp3', $volume_info['volume_id'], $volume_info['chapter_no']));
            if($is) {
                echo '文件`'.$file_name.'`移动成功' . "\n";
            }else {
                echo '文件`'.$file_name.'`移动失败' . "\n";
            }
        }
    }

    /**
     * 导入圣经音频时间表
     */
    public function actionImportAudioSeconds()
    {
        $scripture_time = new ScripturesTime();
        $file = fopen('/mydata/圣经经文时间表.txt', 'r');
        while(!feof($file)) {
            $line = fgets($file);
            $array = explode(' ', $line);
            list($volume_id, $chapter_no, $seconds) = $array;
            $scripture_time->add($volume_id, $chapter_no, $seconds);
        }
    }

    /**
     * 修复朋友关系
     */
    public function actionFixFriends()
    {
        for($i = 1; $i < 908; $i++) {
            $info = Friends::find()->where(['id' => $i])->one();
            $is = Friends::find()->where(['user_id' => $info['friend_user_id'], 'friend_user_id' => $info['user_id']])->one();
            if(!$is) {
                $friends = new Friends();
                $is = $friends->add([
                    'user_id' => $info['friend_user_id'],
                    'friend_user_id' => $info['user_id'],
                    'created_at' => time(),
                    'updated_at' => time(),
                ]);
                if(!$is) {
                    echo $i . "修复失败\n";
                }
                echo $i . "修复成功\n";
            }
        }
    }
}