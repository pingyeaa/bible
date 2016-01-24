<?php

namespace common\models;

use yii\db\ActiveRecord;

class NickList extends ActiveRecord
{
    public function table()
    {
        return 'public.nick_list';
    }

    public function rules()
    {
        return [
            [['order_no', 'nick_id', 'create_at'], 'required']
        ];
    }

    public function add($data)
    {
        $this->isNewRecord = true;
        $this->attributes = $data;
        return $this->save();
    }

    /**
     * 生成用户标识
     * @param int $number 生成数量
     * @return bool
     */
    public function generate($number = 10)
    {
        $info = $this->getMaxInfo();
        $maxNickId = isset($info['nick_id']) ? $info['nick_id'] : '';
        $maxOrderNo = $info['order_no'];

        $count = 0;
        while($count < $number){

            //计算下一级标识
            if($maxNickId == '')
                $nextNickId = 'aaa00';
            else
                $nextNickId = ++$maxNickId;
            $nextOrderNo = ++$maxOrderNo;

            //入库
            $nickList = new NickList();
            $nickList->attributes = [
                'nick_id' => $nextNickId,
                'order_no' => $nextOrderNo,
                'create_at' => time()
            ];
            if(!$nickList->save())
                return false;
            $count++;
        }
        return true;
    }

    /**
     * 获取最大信息
     */
    public function getMaxInfo()
    {
        return $this->find()->orderBy('id desc')->one();
    }

    /**
     * 根据序号查询
     * @param $orderNo
     * @return null|static
     */
    public function getInfoByOrderNo($orderNo)
    {
        return $this->find()->where(['order_no' => $orderNo])->one();
    }
}