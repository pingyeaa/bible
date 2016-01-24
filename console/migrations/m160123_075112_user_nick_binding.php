<?php

use yii\db\Schema;
use yii\db\Migration;

class m160123_075112_user_nick_binding extends Migration
{
    public function up()
    {
        $this->createTable('public.user_nick_binding', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'nick_list_id' => $this->integer()->notNull(),
            'create_at' => $this->integer(10)->notNull(),
        ]);
        $this->execute("comment on table public.user_nick_binding is '活石用户标识与用户id绑定表'");
    }

    public function down()
    {
        $this->dropTable('public.user_nick_binding');
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
