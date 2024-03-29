<?php

use yii\db\Schema;
use yii\db\Migration;

class m130524_201442_init extends Migration
{
    public function up()
    {
        $tableOptions = null;
        $this->createTable('public.user', [
            'id' => $this->primaryKey(),
            'username' => $this->integer(20)->notNull()->unique(),
            'password' => $this->string(32)->notNull(),
            'nickname' => $this->string()->notNull(),
            'gender' => $this->integer(1),
            'birthday' => $this->date(),
            'believe_date' => $this->date(),
            'created_at' => $this->integer()->notNull(),
            'last_login_at' => $this->integer()->notNull(),
        ], $tableOptions);
    }

    public function down()
    {
        $this->dropTable('public.user');
    }
}
