<?php

use yii\db\Schema;
use yii\db\Migration;

class m160113_091918_create_user extends Migration
{
    public function up()
    {
        $this->createTable('public.user', [
            'id' => $this->primaryKey(),
            'nation_code' => $this->integer()->notNull(),
            'username' => $this->string(20)->notNull()->unique(),
            'password' => $this->string(32)->notNull(),
            'nickname' => $this->string(),
            'gender' => $this->integer(1),
            'birthday' => $this->date(),
            'believe_date' => $this->date(),
            'status' => $this->integer()->defaultValue(1),  //1-正常　0-关闭
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
            'last_login_at' => $this->integer(),
        ]);
        return true;
    }

    public function down()
    {
        return $this->dropTable('public.user');
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
