<?php

namespace Inok\RBAC\Lib;

use Exception;
use Inok\RBAC\Lib\Exceptions\RbacPermissionNotFoundException;
use Inok\RBAC\Lib\Exceptions\RbacUserNotProvidedException;

/**
 * Documentation regarding Rbac Manager functionality.
 *
 * Rbac Manager: Provides NIST Level 2 Standard Hierarchical Role Based Access Control
 *
 * Has three members, Roles, Users and Permissions for specific operations
 **/
class RbacManager extends Base {
  public RbacUserManager $users;
  public RoleManager $roles;
  public PermissionManager $permissions;

  public function __construct() {
    $this->users = new RbacUserManager();
    $this->roles = new RoleManager();
    $this->permissions = new PermissionManager();
  }

  /**
   * Assign a role to a permission.
   * Alias for what's in the base class
   *
   * @throws Exception
   */
  public function assign($role, $permission): bool {
    return $this->roles->assign($role, $permission);
  }

  /**
   * Checks whether a user has a permission or not.
   *
   * @throws RbacUserNotProvidedException
   * @throws RbacPermissionNotFoundException
   * @throws Exception
   */
  public function check($permission, $userID = null): bool {
    if ($userID === null) {
      throw new RbacUserNotProvidedException("\$userID is a required argument.");
    }

    $permissionID = $this->getPermissionId($permission);

    // if invalid, throw exception
    if ($permissionID === null) {
      throw new RbacPermissionNotFoundException("The permission '".$permission."' not found.");
    }

    if ($this->isSQLite()) {
      $lastPart = "AS temp ON (tr.id = temp.role_id)
 							     WHERE TUrel.user_id = ?
 							       AND temp.id = ?";
    } else {
      $lastPart = "ON (tr.id = TRel.role_id)
 							     WHERE TUrel.user_id = ?
 							       AND TPdirect.id = ?";
    }
    $res = Jf::sql("SELECT COUNT(*) AS result
                    FROM {$this->tablePrefix()}userroles AS TUrel
                        INNER JOIN {$this->tablePrefix()}roles AS TRdirect ON (TRdirect.id = TUrel.role_id)
                        INNER JOIN {$this->tablePrefix()}roles AS tr ON (tr.lft BETWEEN TRdirect.lft AND TRdirect.rght)
                        INNER JOIN ({$this->tablePrefix()}permissions AS TPdirect
                        INNER JOIN {$this->tablePrefix()}permissions AS tp ON (TPdirect.lft BETWEEN tp.lft AND tp.rght)
                        INNER JOIN {$this->tablePrefix()}rolepermissions AS TRel ON (tp.id = TRel.permission_id)
                        ) ".$lastPart, $userID, $permissionID);

    return $res[0]['result'] >= 1;
  }

  /**
   * Enforce a permission on a user
   *
   * @throws RbacUserNotProvidedException
   * @throws RbacPermissionNotFoundException
   */
  public function enforce($permission, ?int $userID = null): bool {
    if ($userID === null) {
      throw new RbacUserNotProvidedException("\$userID is a required argument.");
    }
    if (!$this->check($permission, $userID)) {
      header('HTTP/1.1 403 Forbidden');
      die("<strong>Forbidden</strong>: You do not have permission to access this resource.");
    }
    return true;
  }

  /**
   * Remove all roles, permissions and assignments
   * mostly used for testing
   *
   * @throws Exception
   */
  public function reset(bool $ensure = false): bool {
    if (!$ensure) {
      throw new Exception("You must pass true to this function, otherwise it won't work.");
    }

    $res = true;
    $res = $res && $this->roles->resetAssignments(true);
    $res = $res && $this->roles->reset(true);
    $res = $res && $this->permissions->reset(true);
    return $res && $this->users->resetAssignments(true);
  }
}
