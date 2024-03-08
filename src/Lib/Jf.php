<?php

namespace Inok\RBAC\Lib;

use Exception;
use PDO;

class Jf {
  public static RbacManager $rbac;

  public static ?PDO $db = null;

  public static ?string $dbDriver = null;

  public static string $tablePrefix;

  private static bool $groupConcatLimitChanged = false;

  private static array $sqlTypes = ["DELETE", "UPDATE", "REPLAC", 'ALTER '];

  public static function setTablePrefix(string $tablePrefix): void {
    self::$tablePrefix = $tablePrefix;
  }

  public static function tablePrefix(): string {
    return self::$tablePrefix;
  }

  public static function detectDbDriver(): void {
    self::$dbDriver = Jf::$db->getAttribute(PDO::ATTR_DRIVER_NAME);
  }

  /**
   * The Jf::sql function. The behavior of this function is as follows:
   *
   * * On queries with no parameters, it should use query function and fetch all results (no prepared statement)
   * * On queries with parameters, parameters are provided as question marks (?) and then additional function arguments will be
   *   bound to question marks.
   * * On SELECT, it will return 2D array of results or NULL if no result.
   * * On DELETE, UPDATE it returns affected rows
   * * On INSERT, if auto-increment is available last insert id, otherwise affected rows
   *
   * @todo currently sqlite always returns sequence number for lastInsertId, so there's no way of knowing if insert worked instead of execute result. all instances of ==1 replaced with >=1 to check for insert
   *
   * @throws Exception
   */
  public static function sql(string $query) {
    $args = func_get_args();
    if (is_null(self::$db)) {
      throw new Exception("Unknown database interface type.");
    }
    return call_user_func_array([__CLASS__, 'sqlPdo'], $args);
  }

  private static function sqlSetConcatMaxLen(array $debugBacktrace): void {
    if (Jf::$dbDriver !== 'mysql') {
      self::$groupConcatLimitChanged = true;
      return;
    }

    if ((isset($debugBacktrace[3])) && ($debugBacktrace[3]['function'] == 'pathId')) {
      $success = self::$db->query("SET SESSION group_concat_max_len = 1000000");
      if ($success) {
        self::$groupConcatLimitChanged = true;
      }
    }
  }

  private static function sqlPdo(string $query) {
    if (!self::$groupConcatLimitChanged) {
      self::sqlSetConcatMaxLen(debug_backtrace());
    }

    $args = func_get_args();
    $query = array_shift($args);
    $type = substr(trim(strtoupper($query)), 0, 6);
    if (empty($args)) {
      return self::simpleQuery($query, $type);
    }
    return self::bindQuery($query, $type, $args);
  }

  private static function simpleQuery(string $query, string $type) {
    $result = self::$db->query($query);
    if ($result === false) {
      return null;
    }
    if (in_array($type, self::$sqlTypes)) {
      return $result->rowCount();
    }
    $res = $result->fetchAll(PDO::FETCH_ASSOC);
    return ($res === []) ? null : $res;
  }

  private static function bindQuery(string $query, string $type, array $args) {
    if (!$stmt = self::$db->prepare($query)) {
      return false;
    }
    $i = 0;
    foreach ($args as &$v) {
      $stmt->bindValue(++$i, $v);
    }

    $success = $stmt->execute();

    if ($type == "INSERT") {
      if (!$success) {
        return null;
      }
      $res = self::$db->lastInsertId();
      return ($res == 0) ? $stmt->rowCount() : $res;
    }
    if (in_array($type, self::$sqlTypes)) {
      return $stmt->rowCount();
    }
    if ($type == "SELECT") {
      $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
      return ($res === []) ? null : $res;
    }
    return null;
  }

  public static function time(): int {
    return time();
  }
}
