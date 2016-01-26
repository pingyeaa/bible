<?php

use yii\db\Schema;
use yii\db\Migration;

class m160126_085625_head_portrait extends Migration
{
    public function up()
    {
        $this->createTable('public.portrait', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->defaultValue(0),
            'portrait_name' => $this->string(40)->notNull(),
            'created_at' => $this->integer()->notNull()
        ]);
    }

    public function down()
    {
        $this->dropTable('public.portrait');
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
