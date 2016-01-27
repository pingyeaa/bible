<?php

use yii\db\Schema;
use yii\db\Migration;

class m160127_093712_api_log extends Migration
{
    public function up()
    {
        $this->createTable('public.api_log', [
            'id' => $this->primaryKey(),
            'route' => $this->string(20)->notNull(),
            'request_type' => $this->string(10)->notNull(), //请求类型
            'url' => $this->text(),
            'params' => $this->text(),
            'response' => $this->text(),
            'status' => $this->integer()->notNull(),
            'memory' => $this->bigInteger(),
            'response_time' => $this->float(),
            'ip' => $this->string(20),
            'created_at' => $this->integer()
        ]);
    }

    public function down()
    {
        $this->dropTable('public.api_log');
    }

    /*
    // Use safeUp/safeDown to run migration code within a transaction
    public function safeUp()
    {
    }

    public function safeDown()
    {
    }
    */
}
