<?php

namespace frontend\components\push;

use yii\base\Component;
require "jpush/JPush.php";


class Push extends Component
{
    public $app_key;
    public $master_secret;
    public $log_file_path;
    public $retry_times;

    protected $push_handle;

    public function init()
    {
        parent::init();
        $this->push_handle = new \JPush($this->app_key, $this->master_secret, $this->log_file_path, $this->retry_times);
    }

    public function __call($method, $args = [])
    {
        return call_user_func_array(array($this->push_handle, $method), $args);
    }
}