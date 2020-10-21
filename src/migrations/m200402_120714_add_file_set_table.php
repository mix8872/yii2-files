<?php
namespace mix8872\yiiFiles\migrations;

use yii\db\Migration;

class m200402_120714_add_file_set_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%file_set}}', [
            'id' => $this->primaryKey(),
            'file_id' => $this->integer(11),
            'order' => $this->integer(3),
            'filename' => $this->string(255),
            'data' => $this->string(255)
        ]);

        $this->createIndex('idx-file_set_id', 'file_set', 'file_id');
        $this->addForeignKey(
            'fk-file_set-file_id',
            'file_set',
            'file_id',
            'file',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-file_set-file_id', 'file_set');
        $this->dropIndex('idx-file_set_id', 'file_set');
        $this->dropTable('{{%file_set}}');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m171029_110953_files_table cannot be reverted.\n";

        return false;
    }
    */
}
