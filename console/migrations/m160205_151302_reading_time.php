<?php

use yii\db\Schema;
use yii\db\Migration;

class m160205_151302_reading_time extends Migration
{
    public function up()
    {
        $this->createTable('public.reading_time', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'minutes' => $this->integer()->notNull(),
            'days' => $this->integer()->notNull(),
            'created_at' => $this->integer(),
            'updated_at' => $this->integer(),
        ]);
    }

    public function down()
    {
        $this->dropTable('public.reading_time');
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
