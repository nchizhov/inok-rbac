<?php

namespace Inok\RBAC\Lib;

use Exception;
use Inok\RBAC\Lib\Nestedset\FullNestedSet;

/**
 * Documentation regarding Role Manager functionality.
 *
 * Role Manager: Contains functionality specific to Roles
 **/
class RoleManager extends BaseRbac {
  /**
   * Roles Nested Set
   **/
  protected FullNestedSet $roles;

  protected function type(): string {
    return "roles";
  }

  public function __construct() {
    $this->roles = new FullNestedSet($this->tablePrefix() . "roles");
  }

  /**
   * Remove roles from system
   *
   * @throws Exception
   */
  public function remove(int $id, bool $recursive = false): bool {
    $this->unassignPermissions($id);
    $this->unassignUsers($id);
    if (!$recursive) {
      return $this->roles->deleteConditional("id = ?", $id);
    }
    return $this->roles->deleteSubtreeConditional("id = ?", $id);
  }

  /**
   * Un assigns all permissions belonging to a role
   *
   * @throws Exception
   */
  public function unassignPermissions(int $id): int {
    return Jf::sql("DELETE 
                    FROM {$this->tablePrefix()}rolepermissions 
                    WHERE role_id = ?", $id);
  }

  /**
   * Un assign all users that have a certain role
   *
   * @throws Exception
   */
  public function unassignUsers(int $id): int {
    return Jf::sql("DELETE 
                    FROM {$this->tablePrefix()}userroles 
                    WHERE role_id = ?", $id);
  }

  /**
   * Checks to see if a role has a permission or not
   *
   * @throws Exception
   */
  public function hasPermission(int $role, int $permission): bool {
    $res = Jf::sql("SELECT COUNT(*) AS result
					          FROM {$this->tablePrefix()}rolepermissions AS TRel
					              INNER JOIN {$this->tablePrefix()}permissions AS tp ON (tp.id = TRel.permission_id)
					              INNER JOIN {$this->tablePrefix()}roles AS tr ON (tr.id = TRel.role_id)
					          WHERE tr.lft BETWEEN
					              (SELECT lft 
					               FROM {$this->tablePrefix()}roles 
					               WHERE id = ?)
					            AND
					              (SELECT rght 
					               FROM {$this->tablePrefix()}roles 
					               WHERE id = ?)
					/* the above section means any row that is a descendants of our role (if descendant roles have some permission, then our role has it two) */
					            AND tp.id IN (
					              SELECT parent.id
					              FROM {$this->tablePrefix()}permissions AS node,
					                   {$this->tablePrefix()}permissions AS parent
					              WHERE node.lft BETWEEN parent.lft AND parent.rght
					                AND node.id = ?
					          ORDER BY parent.lft)
					/*
					the above section returns all the parents of (the path to) our permission, so if one of our role or its descendants
					has an assignment to any of them, we're good.
					*/
					", $role, $role, $permission);
    return $res[0]['result'] >= 1;
  }

  /**
   * Returns all permissions assigned to a role
   *
   * @throws Exception
   */
  public function permissions($role, bool $onlyIDs = true): ?array {
    if (!is_numeric($role)) {
      $role = $this->returnId($role);
    }

    if (!$onlyIDs) {
      $res = Jf::sql("SELECT `tp`.id, `tp`.title, `tp`.description 
                      FROM {$this->tablePrefix()}permissions AS `tp`
		                    LEFT JOIN {$this->tablePrefix()}rolepermissions AS `tr` ON (`tr`.permission_id = `tp`.id)   
		                  WHERE role_id = ? 
		                  ORDER BY tp.id", $role);
      return ($res) ? self::mapSqlData($res, ['id']) : null;
    }

    $res = Jf::sql("SELECT permission_id AS `id` 
                    FROM {$this->tablePrefix()}rolepermissions 
                    WHERE role_id = ? 
                    ORDER BY permission_id", $role);
    if (!is_array($res)) {
      return null;
    }
    return array_map('intval', array_column($res, 'id'));
  }
}
