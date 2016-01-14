<?php

use yii\db\Schema;
use yii\db\Migration;

class m160113_083943_sms_register_binding extends Migration
{
    public function up()
    {
        $this->createTable('public.sms_register_binding', [
            'id' => $this->primaryKey(),
            'nation_code' => $this->integer()->notNull(),
            'phone' => $this->string(20)->notNull(),
            'code' => $this->integer(6)->notNull(),
            'status' => $this->integer(1)->defaultValue(0),
            'create_at' => $this->integer(10)->notNull(),
        ]);
    }

    public function down()
    {
        return $this->dropTable('public.sms_register_binding');
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
