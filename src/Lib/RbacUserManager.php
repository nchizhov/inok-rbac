<?php

namespace Inok\RBAC\Lib;

use Exception;
use Inok\RBAC\Lib\Exceptions\RbacUserNotProvidedException;

/**
 * Documentation regarding Rbac User Manager functionality.
 *
 * Rbac User Manager: Contains functionality specific to Users
 **/
class RbacUserManager extends Base {
  /**
   * Checks to see whether a user has a role or not
   *
   * @throws RbacUserNotProvidedException
   * @throws Exception
   */
  public function hasRole($role, ?int $userID = null): bool {
    if (is_null($role)) {
      return false;
    }

    if ($userID === null) {
      throw new RbacUserNotProvidedException("\$userID is a required argument.");
    }

    $roleID = $this->getRoleId($role);

    $r = Jf::sql("SELECT tr.id 
                 FROM {$this->tablePrefix()}userroles AS tur
			              INNER JOIN {$this->tablePrefix()}roles AS TRdirect ON (TRdirect.id = tur.role_id)
			              INNER JOIN {$this->tablePrefix()}roles AS tr ON (tr.lft BETWEEN TRdirect.lft AND TRdirect.rght)
			           WHERE tur.user_id = ? AND tr.id = ?", $userID, $roleID);
    return $r !== null;
  }

  /**
   * Assigns a role to a user
   *
   * @throws RbacUserNotProvidedException
   * @throws Exception
   */
  public function assign($role, ?int $userID = null): bool {
    if ($userID === null) {
      throw new RbacUserNotProvidedException("\$userID is a required argument.");
    }

    $roleID = $this->getRoleId($role);

    $res = Jf::sql("INSERT INTO {$this->tablePrefix()}userroles (user_id, role_id, assignment_date)
				            VALUES (?, ?, ?)", $userID, $roleID, Jf::time());
    return $res >= 1;
  }

  /**
   * Un assigns a role from a user
   *
   * @throws RbacUserNotProvidedException
   * @throws Exception
   */
  public function unassign($role, ?int $userID = null): bool {
    if ($userID === null) {
      throw new RbacUserNotProvidedException("\$userID is a required argument.");
    }

    $roleID = $this->getRoleId($role);

    return Jf::sql("DELETE 
                    FROM {$this->tablePrefix()}userroles 
                    WHERE user_id = ? AND role_id = ?", $userID, $roleID) >= 1;
  }

  /**
   * Returns all roles of a user
   *
   * @throws RbacUserNotProvidedException
   * @throws Exception
   */
  public function allRoles(?int $userID = null): ?array {
    if ($userID === null) {
      throw new RbacUserNotProvidedException("\$userID is a required argument.");
    }

    $res = Jf::sql("SELECT tr.id, tr.lft, tr.rght, tr.title, tr.description
			              FROM {$this->tablePrefix()}userroles AS `TRel`
			                  INNER JOIN {$this->tablePrefix()}roles AS `tr` ON	(`TRel`.role_id = `tr`.id)
			              WHERE TRel.user_id = ?", $userID);
    return ($res) ? self::mapSqlData($res, ['id', 'lft', 'rght']) : null;
  }

  /**
   * Return count of roles assigned to a user
   *
   * @throws RbacUserNotProvidedException
   * @throws Exception
   */
  public function roleCount(?int $userID = null): int {
    if ($userID === null) {
      throw new RbacUserNotProvidedException("\$userID is a required argument.");
    }

    $res = Jf::sql("SELECT COUNT(*) AS result 
                    FROM {$this->tablePrefix()}userroles 
                    WHERE user_id = ?", $userID);
    return (int)$res[0]['result'];
  }

  /**
   * Remove all role-user relations
   * mostly used for testing
   *
   * @throws Exception
   */
  public function resetAssignments(bool $ensure = false): int {
    if (!$ensure) {
      throw new Exception("You must pass true to this function, otherwise it won't work.");
    }
    $res = Jf::sql("DELETE 
                    FROM {$this->tablePrefix()}userroles");

    $adapter = get_class(Jf::$db);
    if ($this->isMySql()) {
      Jf::sql("ALTER TABLE {$this->tablePrefix()}userroles AUTO_INCREMENT = 1");
    } elseif ($this->isSQLite()) {
      Jf::sql("DELETE 
               FROM sqlite_sequence 
               WHERE name = ? ", $this->tablePrefix() . "_userroles");
    } else {
      throw new Exception("Rbac can not reset table on this type of database: ".$adapter);
    }
    $this->assign("root", 1 /* root user */);
    return $res;
  }
}
