<?php

namespace Inok\RBAC\Lib\Setup;

use Inok\RBAC\Lib\Interfaces\DbSetup;
use Inok\RBAC\Lib\Jf;
use PDO;

class RbacSetupMySql implements DbSetup {
  public static function setup(): void {
    $db = Jf::$db;
    $db->exec("CREATE TABLE IF NOT EXISTS `".Jf::tablePrefix()."permissions` (
                            `id` int(11) NOT NULL auto_increment,
                            `lft` int(11) NOT NULL,
                            `rght` int(11) NOT NULL,
                            `title` char(64) NOT NULL,
                            `description` text NOT NULL,
                            PRIMARY KEY (`id`),
                            KEY `title` (`title`),
                            KEY `lft` (`lft`),
                            KEY `rght` (`rght`)
                        ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = 'RBAC Permissions List' AUTO_INCREMENT = 1");
    $db->exec("CREATE TABLE IF NOT EXISTS `".Jf::tablePrefix()."rolepermissions` (
                            `role_id` int(11) NOT NULL,
                            `permission_id` int(11) NOT NULL,
                            `assignment_date` int(11) NOT NULL,
                            PRIMARY KEY (`role_id`, `permission_id`)
                        ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = 'RBAC Roles Permissions List'");
    $db->exec("CREATE TABLE IF NOT EXISTS `".Jf::tablePrefix()."roles` (
                            `id` int(11) NOT NULL auto_increment,
                            `lft` int(11) NOT NULL,
                            `rght` int(11) NOT NULL,
                            `title` varchar(128) NOT NULL,
                            `description` text NOT NULL,
                            PRIMARY KEY (`id`),
                            KEY `title` (`title`),
                            KEY `lft` (`lft`),
                            KEY `rght` (`rght`)
                        ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = 'RBAC Roles List'");
    $db->exec("CREATE TABLE IF NOT EXISTS `".Jf::tablePrefix()."userroles` (
                            `user_id` int(11) NOT NULL,
                            `role_id` int(11) NOT NULL,
                            `assignment_date` int(11) NOT NULL,
                            PRIMARY KEY (`user_id`, `role_id`)
                        ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = 'RBAC Users Roles List'");

    $db->exec("INSERT INTO `".Jf::tablePrefix()."permissions` (`id`, `lft`, `rght`, `title`, `description`)
                VALUES (1, 0, 1, 'root', 'root')");
    $db->exec("INSERT INTO `".Jf::tablePrefix()."rolepermissions` (`role_id`, `permission_id`, `assignment_date`)
                VALUES (1, 1, UNIX_TIMESTAMP())");
    $db->exec("INSERT INTO `".Jf::tablePrefix()."roles` (`id`, `lft`, `rght`, `title`, `description`)
                VALUES (1, 0, 1, 'root', 'root')");
    $db->exec("INSERT INTO `".Jf::tablePrefix()."userroles` (`user_id`, `role_id`, `assignment_date`)
                VALUES (1, 1, UNIX_TIMESTAMP())");
  }

  public static function getTables(): array {
    return Jf::$db->query("SHOW TABLES 
                          LIKE '".Jf::tablePrefix()."%'")->fetchAll(PDO::FETCH_COLUMN);
  }
}
