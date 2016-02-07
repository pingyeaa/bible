<?php

use yii\db\Schema;
use yii\db\Migration;

class m160207_065133_intercession extends Migration
{
    public function up()
    {
        //代祷表
        $this->createTable('public.intercession', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'content' => $this->text()->notNull(),
            'ip' => $this->string(20),
            'comments' => $this->integer(),     //评论数
            'intercessions' => $this->integer(),    //代祷次数
            'privacy' => $this->integer(),    //是否隐私（0-否，1-是，仅一维好友可见）
            'created_at' => $this->integer(),
            'updated_at' => $this->integer(),
        ]);

        //代祷评论表
        $this->createTable('public.intercession_comments', [
            'id' => $this->primaryKey(),
            'intercession_id' => $this->integer()->notNull(),
            'user_id' => $this->integer()->notNull(),
            'comment_by_id' => $this->integer()->notNull(), //评论者id
            'content' => $this->text()->notNull(),
            'ip' => $this->string(20),
            'created_at' => $this->integer(),
            'updated_at' => $this->integer()
        ]);

        //加入代祷表
        $this->createTable('public.intercession_join', [
            'id' => $this->primaryKey(),
            'intercession_id' => $this->integer()->notNull(),
            'user_id' => $this->integer()->notNull(),
            'intercessors_id' => $this->integer()->notNull(), //加入代祷的人id
            'ip' => $this->string(20),
            'created_at' => $this->integer(),
            'updated_at' => $this->integer()
        ]);

        //评论点赞表
        $this->createTable('public.intercession_comment_praise', [
            'id' => $this->primaryKey(),
            'comment_id' => $this->integer()->notNull(),    //评论id
            'user_id' => $this->integer()->notNull(),
            'praise_user_id' => $this->integer()->notNull(), //点赞者id
            'ip' => $this->string(20),
            'created_at' => $this->integer(),
            'updated_at' => $this->integer()
        ]);
    }

    public function down()
    {
        $this->dropTable('public.intercession');
        $this->dropTable('public.intercession_comments');
        $this->dropTable('public.intercession_join');
        $this->dropTable('public.intercession_comment_praise');
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
