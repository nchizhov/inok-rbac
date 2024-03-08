<?php

namespace Inok\RBAC;

use Exception;
use Inok\RBAC\Lib\Exceptions\RbacTablesException;
use Inok\RBAC\Lib\Exceptions\RbacUnsupportedDriverException;
use Inok\RBAC\Lib\Jf;
use Inok\RBAC\Lib\PermissionManager;
use Inok\RBAC\Lib\RbacManager;
use Inok\RBAC\Lib\RbacUserManager;
use Inok\RBAC\Lib\RoleManager;
use Inok\RBAC\Lib\Setup\RbacDbSetup;
use PDO;

/**
 * Provides NIST Level 2 Standard Role Based Access Control functionality
 **/
class Rbac {
  public PermissionManager $permissions;
  public RoleManager $roles;
  public RbacUserManager $users;

  private array $supportedPDODrivers = ['sqlite', 'mysql'];

  /**
   * @throws RbacUnsupportedDriverException
   * @throws Exception
   */
  public function __construct(PDO $db, string $tablePrefix = 'phprbac_') {
    Jf::$db = $db;
    Jf::detectDbDriver();
    if (!in_array(Jf::$dbDriver, $this->supportedPDODrivers)) {
      throw new RbacUnsupportedDriverException('Current PDO Driver '.Jf::$dbDriver.' not supported');
    }
    Jf::setTablePrefix($tablePrefix);

    if (!RbacDbSetup::setup()) {
      throw new RbacTablesException("Cannot create required rbac-tables");
    }

    Jf::$rbac = new RbacManager();

    $this->permissions = Jf::$rbac->permissions;
    $this->roles = Jf::$rbac->roles;
    $this->users = Jf::$rbac->users;
  }

  /**
   * @throws Exception
   */
  public function assign($role, $permission): bool {
    return Jf::$rbac->assign($role, $permission);
  }

  /**
   * @throws Exception
   */
  public function check($permission, $userId): bool {
    return Jf::$rbac->check($permission, $userId);
  }

  /**
   * @throws Exception
   */
  public function enforce($permission, ?int $userId): bool {
    return Jf::$rbac->enforce($permission, $userId);
  }

  /**
   * @throws Exception
   */
  public function reset(bool $ensure = false): bool {
    return Jf::$rbac->reset($ensure);
  }

  /**
   * @throws Exception
   */
  public function tablePrefix(): string {
    return Jf::$rbac->tablePrefix();
  }
}
