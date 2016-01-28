<?php

use yii\db\Schema;
use yii\db\Migration;

class m160128_150133_user_province_city extends Migration
{
    public function up()
    {
        $this->addColumn('public.user', 'province_id', $this->integer()->defaultValue(0));
        $this->addColumn('public.user', 'city_id', $this->integer()->defaultValue(0));
    }

    public function down()
    {
        $this->dropColumn('public.user', 'province_id');
        $this->dropColumn('public.user', 'city_id');
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
