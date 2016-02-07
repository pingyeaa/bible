<?php

use yii\db\Schema;
use yii\db\Migration;

class m160206_071437_friends extends Migration
{
    public function up()
    {
        $this->createTable('public.friends', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'friend_user_id' => $this->integer()->notNull(),
            'created_at' => $this->integer(),
            'updated_at' => $this->integer(),
        ]);
    }

    public function down()
    {
        $this->dropTable('public.friends');
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
