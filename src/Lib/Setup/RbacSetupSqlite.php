<?php

namespace Inok\RBAC\Lib\Setup;

use Inok\RBAC\Lib\Interfaces\DbSetup;
use Inok\RBAC\Lib\Jf;
use PDO;

class RbacSetupSqlite implements DbSetup {
  public static function setup(): void {
    $db = Jf::$db;
    $db->exec("CREATE TABLE `".Jf::tablePrefix()."permissions` (
                    `id` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
                    `lft` INTEGER NOT NULL,
                    `rght` INTEGER NOT NULL,
                    `title` char(64) NOT NULL,
                    `description` text NOT NULL)");
    $db->exec("CREATE TABLE `".Jf::tablePrefix()."rolepermissions` (
                    `role_id` INTEGER NOT NULL,
                    `permission_id` INTEGER NOT NULL,
                    `assignment_date` INTEGER NOT NULL,
                    PRIMARY KEY  (`role_id`,`permission_id`))");
    $db->exec("CREATE TABLE `".Jf::tablePrefix()."roles` (
                    `id` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
                    `lft` INTEGER NOT NULL,
                    `rght` INTEGER NOT NULL,
                    `title` varchar(128) NOT NULL,
                    `description` text NOT NULL)");
    $db->exec("CREATE TABLE `".Jf::tablePrefix()."userroles` (
                    `user_id` INTEGER NOT NULL,
                    `role_id` INTEGER NOT NULL,
                    `assignment_date` INTEGER NOT NULL,
                    PRIMARY KEY  (`user_id`,`role_id`))");

    $db->exec("INSERT INTO `".Jf::tablePrefix()."permissions` (`id`, `lft`, `rght`, `title`, `description`)
                  VALUES (1, 0, 1, 'root', 'root')");
    $db->exec("INSERT INTO `".Jf::tablePrefix()."rolepermissions` (`role_id`, `permission_id`, `assignment_date`)
                  VALUES (1, 1, strftime('%s', 'now'))");
    $db->exec("INSERT INTO `".Jf::tablePrefix()."roles` (`id`, `lft`, `rght`, `title`, `description`)
                  VALUES (1, 0, 1, 'root', 'root')");
    $db->exec("INSERT INTO `".Jf::tablePrefix()."userroles` (`user_id`, `role_id`, `assignment_date`)
                  VALUES (0, 1, strftime('%s', 'now'))");
  }

  public static function getTables(): array {
    return Jf::$db->query("SELECT name
                           FROM sqlite_schema
                           WHERE type = 'table' AND name LIKE '".Jf::tablePrefix()."%'")->fetchAll(PDO::FETCH_COLUMN);
  }
}
