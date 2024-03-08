<?php

namespace Inok\RBAC\Lib\Setup;

use Inok\RBAC\Lib\Interfaces\DbSetup;
use Inok\RBAC\Lib\Jf;

class RbacDbSetup {
  private static array $tables = ['permissions', 'rolepermissions', 'roles', 'userroles'];

  /** @param array<string, DbSetup> $pdoSetup */
  private static array $pdoSetup = ['mysql' => RbacSetupMySql::class,
                                    'sqlite' => RbacSetupSqlite::class];

  public static function setup(): bool {
    if (!array_key_exists(Jf::$dbDriver, self::$pdoSetup)) {
      return false;
    }
    $dbClass = self::$pdoSetup[Jf::$dbDriver];
    $baseTables = self::fillTables();
    $tables = $dbClass::getTables();
    if (self::equalTables($baseTables, $tables)) {
      return true;
    }
    if (count($tables) !== 0) {
      return false;
    }
    $dbClass::setup();
    return true;
  }

  private static function fillTables(): array {
    return array_map(function(string $value) {
      return Jf::$tablePrefix.$value;
    }, self::$tables);
  }

  private static function equalTables($baseTables, $tables): bool {
    return (count($baseTables) == count($tables) && array_diff($baseTables, $tables) === array_diff($tables, $baseTables));
  }
}
