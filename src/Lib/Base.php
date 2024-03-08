<?php

namespace Inok\RBAC\Lib;

use Exception;

class Base extends JModel {
  /**
   * @throws Exception
   */
  protected function getRoleId($role) {
    if (is_numeric($role)) {
      return $role;
    }
    if (substr($role, 0, 1) == "/") {
      return Jf::$rbac->roles->pathId($role);
    }
    return Jf::$rbac->roles->titleId($role);
  }

  /**
   * @throws Exception
   */
  protected function getPermissionId($permission) {
    if (is_numeric($permission)) {
      return $permission;
    }
    if (substr($permission, 0, 1) == "/") {
      return Jf::$rbac->permissions->pathId($permission);
    }
    return Jf::$rbac->permissions->titleId($permission);
  }

  public static function mapSqlData(array $data, array $intFields = []): array {
    if (empty($intFields)) {
      return $data;
    }
    $newData = [];
    $intDataFields = [];
    foreach ($intFields as $intField) {
      $intDataFields[$intField] = array_map('intval', array_column($data, $intField));
    }
    foreach ($data as $index => $d) {
      foreach ($intFields as $intField) {
        $d[$intField] = $intDataFields[$intField][$index];
      }
      $newData[] = $d;
    }
    return $newData;
  }
}
