<?php

use yii\db\Schema;
use yii\db\Migration;

class m160123_055056_nick_list extends Migration
{
    public function up()
    {
        $this->createTable('public.nick_list', [
            'id' => $this->primaryKey(),
            'order_no' => $this->integer()->notNull()->unique(),
            'nick_id' => $this->string(20)->notNull()->unique(),
            'create_at' => $this->integer(10)->notNull(),
        ]);
        $this->execute("comment on table public.nick_list is '活石用户id表，不是数据库主键'");
    }

    public function down()
    {
        $this->dropTable('public.nick_list');
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
