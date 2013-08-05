<?php

namespace Claroline\ScormBundle\Migrations\drizzle_pdo_mysql;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated migration based on mapping information: modify it with caution
 *
 * Generation date: 2013/08/05 11:05:53
 */
class Version20130805110551 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
            CREATE TABLE claro_scorm_info (
                id INT AUTO_INCREMENT NOT NULL, 
                user_id INT DEFAULT NULL, 
                scorm_id INT DEFAULT NULL, 
                score_raw INT DEFAULT NULL, 
                score_min INT DEFAULT NULL, 
                score_max INT DEFAULT NULL, 
                lesson_status VARCHAR(255) DEFAULT NULL, 
                session_time INT DEFAULT NULL, 
                total_time INT DEFAULT NULL, 
                entry VARCHAR(255) DEFAULT NULL, 
                suspend_data VARCHAR(255) DEFAULT NULL, 
                credit VARCHAR(255) DEFAULT NULL, 
                exit_mode VARCHAR(255) DEFAULT NULL, 
                lesson_location VARCHAR(255) DEFAULT NULL, 
                lesson_mode VARCHAR(255) DEFAULT NULL, 
                PRIMARY KEY(id), 
                INDEX IDX_6F4BB916A76ED395 (user_id), 
                INDEX IDX_6F4BB916D75F22BE (scorm_id)
            )
        ");
        $this->addSql("
            CREATE TABLE claro_scorm (
                id INT NOT NULL, 
                hash_name VARCHAR(36) NOT NULL, 
                mastery_score INT DEFAULT NULL, 
                launch_data VARCHAR(255) DEFAULT NULL, 
                entry_url VARCHAR(255) NOT NULL, 
                PRIMARY KEY(id)
            )
        ");
        $this->addSql("
            ALTER TABLE claro_scorm_info 
            ADD CONSTRAINT FK_6F4BB916A76ED395 FOREIGN KEY (user_id) 
            REFERENCES claro_user (id)
        ");
        $this->addSql("
            ALTER TABLE claro_scorm_info 
            ADD CONSTRAINT FK_6F4BB916D75F22BE FOREIGN KEY (scorm_id) 
            REFERENCES claro_scorm (id)
        ");
        $this->addSql("
            ALTER TABLE claro_scorm 
            ADD CONSTRAINT FK_B6416871BF396750 FOREIGN KEY (id) 
            REFERENCES claro_resource (id) 
            ON DELETE CASCADE
        ");
    }

    public function down(Schema $schema)
    {
        $this->addSql("
            ALTER TABLE claro_scorm_info 
            DROP FOREIGN KEY FK_6F4BB916D75F22BE
        ");
        $this->addSql("
            DROP TABLE claro_scorm_info
        ");
        $this->addSql("
            DROP TABLE claro_scorm
        ");
    }
}