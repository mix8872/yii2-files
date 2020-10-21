<?php
namespace mix8872\yiiFiles\migrations;

use yii\db\Migration;

class m180912_174505_add_file_content_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%file_content}}', [
            'id' => $this->primaryKey(),
            'file_id' => $this->integer(11),
            'lang' => $this->string()->defaultValue(''),
            'name' => $this->string()->defaultValue(''),
            'title' => $this->string()->defaultValue(''),
            'description' => $this->string(),
        ]);

        $this->createIndex('idx-file_id', 'file_content', 'file_id');
        $this->addForeignKey(
            'fk-file-file_id',
            'file_content',
            'file_id',
            'file',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-file-file_id', 'file_content');
        $this->dropIndex('idx-file_id', 'file_content');
        $this->dropTable('{{%file_content}}');
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
