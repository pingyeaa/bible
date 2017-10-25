<?php

namespace console\controllers;

use common\models\AskedDaily;
use common\models\NickList;
use common\models\Scriptures;
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
        $volume_list = Volume::find()->orderBy('id asc')->all();
        foreach($volume_list as $volume) {
            $scriptures_list = Scriptures::find()->where(['volume_id' => $volume['id']])->orderBy('chapter_no asc, verse_no asc')->all();
            foreach($scriptures_list as $scripture) {
                $scripture_file = '/tmp/bible/' . str_pad($scripture['chapter_no'], 2, '0', STR_PAD_LEFT) . '.txt';
                echo sprintf('卷：%s 章：%s 节：%s', $volume['full_name'], $scripture['chapter_no'], $scripture['verse_no']);
                file_put_contents($scripture_file, sprintf("%d:%d %s\n", $scripture['chapter_no'], $scripture['verse_no'], $scripture['lection']), FILE_APPEND);
            }
        }
    }
}