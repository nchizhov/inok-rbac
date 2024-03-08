<?php

namespace Inok\RBAC\Lib;

use Exception;
use Inok\RBAC\Lib\Nestedset\FullNestedSet;

/**
 * Documentation regarding Permission Manager functionality.
 *
 * Permission Manager: Contains functionality specific to Permissions
 **/
class PermissionManager extends BaseRbac {
  /**
   * Permissions Nested Set
   **/
  protected FullNestedSet $permissions;

  protected function type(): string {
    return "permissions";
  }

  public function __construct() {
    $this->permissions = new FullNestedSet($this->tablePrefix() . "permissions");
  }

  /**
   * Remove permissions from system
   *
   * @throws Exception
   */
  public function remove(int $id, bool $recursive = false): bool {
    $this->unassignRoles($id);
    if (!$recursive) {
      return $this->permissions->deleteConditional("id = ?", $id);
    }
    return $this->permissions->deleteSubtreeConditional("id = ?", $id);
  }

  /**
   * Un assign all roles of this permission, and returns their number
   *
   * @throws Exception
   */
  public function unassignRoles(int $id): int {
    $res = Jf::sql("DELETE 
                    FROM {$this->tablePrefix()}rolepermissions 
                    WHERE permission_id = ?", $id);
    return (int)$res;
  }

  /**
   * Returns all roles assigned to a permission
   *
   * @throws Exception
   */
  public function roles($permission, bool $onlyIDs = true): ?array {
    if (!is_numeric($permission)) {
      $permission = $this->returnId($permission);
    }

    if (!$onlyIDs) {
      $res = Jf::sql("SELECT `tp`.id, `tp`.title, `tp`.description 
                     FROM {$this->tablePrefix()}roles AS `tp`
    		                LEFT JOIN {$this->tablePrefix()}rolepermissions AS `tr` ON (`tr`.role_id = `tp`.id)
    		            WHERE permission_id = ? 
    		            ORDER BY tp.id", $permission);
      return ($res) ? self::mapSqlData($res, ['id']) : null;
    }

    $res = Jf::sql("SELECT role_id AS `id` 
                     FROM	{$this->tablePrefix()}rolepermissions 
                     WHERE permission_id = ? 
                     ORDER BY role_id", $permission);

    if (!is_array($res)) {
      return null;
    }
    return array_map('intval', array_column($res, 'id'));
  }
}
