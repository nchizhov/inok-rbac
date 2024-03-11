<?php

namespace Inok\RBAC\Lib;

use Exception;
use PDO;

/**
 * Rbac base class, it contains operations that are essentially the same for
 * permissions and roles and is inherited by both
 **/
abstract class BaseRbac extends Base {
  public function rootId(): int {
    return 1;
  }

  /**
   * Return type of current instance, e.g. roles, permissions
   **/
  abstract protected function type(): string;

  private function fullTable(): string {
    return $this->tablePrefix().$this->type();
  }

  /**
   * Adds a new role or permission
   * Returns new entry's id
   **/
  public function add(string $title, string $description, ?int $parentID = null): int {
    if ($parentID === null) {
      $parentID = $this->rootId();
    }
    return (int)$this->{$this->type()}->insertChildData(["title" => $title,
                                                         "description" => $description], "id = ?", $parentID);
  }

  /**
   * Adds a path and all its components.
   * Will not replace or create siblings if a component exists.
   *
   * @throws Exception
   */
  public function addPath(string $path, ?array $descriptions = null): int {
    if ($path[0] !== "/") {
      throw new Exception ("The path supplied is not valid.");
    }

    $path = substr(rtrim($path, '/'), 1);
    $parts = explode("/", $path);
    $parent = 1;
    $currentPath = "";
    $nodesCreated = 0;

    foreach ($parts as $index => $p) {
      $description = $descriptions[$index] ?? "";
      $currentPath .= "/".$p;
      $t = $this->pathId($currentPath);
      if (!$t) {
        $iid = $this->add($p, $description, $parent);
        $parent = $iid;
        $nodesCreated++;
      } else {
        $parent = $t;
      }
    }

    return $nodesCreated;
  }

  /**
   * Return count of the entity
   *
   * @throws Exception
   */
  public function count(): int {
    $res = Jf::sql("SELECT COUNT(*) AS `count`
                    FROM {$this->fullTable()}");
    return (int)$res[0]['count'];
  }

  /**
   * Returns id of entity
   *
   * @throws Exception
   */
  public function returnId(?string $entity = null): ?int {
    if (is_null($entity)) {
      return null;
    }
    if (substr($entity, 0, 1) == "/") {
      $entityID = $this->pathId($entity);
    } else {
      $entityID = $this->titleId($entity);
    }
    return $entityID;
  }

  /**
   * Returns id of a path
   *
   * @throws Exception
   * @todo this has a limit of 1000 characters on $path
   **/
  public function pathId(string $path): ?int {
    $path = "root" . $path;

    if ($path[strlen($path) - 1] == "/") {
      $path = substr($path, 0, strlen($path) - 1);
    }
    $parts = explode("/", $path);

    $adapter = get_class(Jf::$db);
    if ($adapter == "mysqli" || ($adapter == "PDO" && Jf::$db->getAttribute(PDO::ATTR_DRIVER_NAME) == "mysql")) {
      $groupConcat = "GROUP_CONCAT(parent.title ORDER BY parent.lft SEPARATOR '/')";
    } elseif ($adapter == "PDO" && Jf::$db->getAttribute(PDO::ATTR_DRIVER_NAME) == "sqlite") {
      $groupConcat = "GROUP_CONCAT(parent.title, '/')";
    } else {
      throw new Exception("Unknown Group_Concat on this type of database: ".$adapter);
    }

    $res = Jf::sql("SELECT node.id, ".$groupConcat." AS path
				            FROM {$this->fullTable()} AS node,
				                 {$this->fullTable()} AS parent
				            WHERE node.lft BETWEEN parent.lft AND parent.rght
				              AND node.title = ?
				            GROUP BY node.id
				            HAVING path = ?", $parts[count($parts) - 1], $path);

    if ($res) {
      return (int) $res[0]['id'];
    }
    return null;
  }

  /**
   * Returns ID belonging to a title, and the first one on that
   **/
  public function titleId(string $title): ?int {
    return $this->{$this->type()}->getID("title = ?", $title);
  }

  /**
   * Return the whole record of a single entry (including rght and lft fields)
   **/
  protected function getRecord(string $condition) {
    $args = func_get_args();
    return call_user_func_array([$this->{$this->type()}, "getRecord"], $args);
  }

  /**
   * Returns title of entity
   **/
  public function getTitle(int $id): ?string {
    $r = $this->getRecord("id = ?", $id);
    return ($r) ? $r['title'] : null;
  }

  /**
   * Returns path of a node
   **/
  public function getPath(int $id): ?string {
    $res = $this->{$this->type()}->pathConditional("id = ?", $id);
    if (empty($res) || !is_array($res)) {
      return null;
    }
    $out = null;
    foreach ($res as $r) {
      if ($r['id'] == 1) {
        $out = '/';
      } else {
        $out .= "/" . $r['title'];
      }
    }
    if (strlen($out) > 1) {
      return substr($out, 1);
    }
    return $out;
  }

  /**
   * Return description of entity
   **/
  public function getDescription(int $id): ?string {
    $r = $this->getRecord("id = ?", $id);
    return ($r) ? $r['description'] : null;
  }

  /**
   * Edits an entity, changing title and/or description. Maintains id.
   **/
  public function edit(int $id, ?string $newTitle = null, ?string $newDescription = null): bool {
    $data = [];
    if ($newTitle !== null) {
      $data['title'] = $newTitle;
    }
    if ($newDescription !== null) {
      $data['description'] = $newDescription;
    }
    if (empty($data)) {
      return false;
    }
    return $this->{$this->type()}->editData($data, "id = ?", $id) == 1;
  }

  /**
   * Returns children of an entity
   **/
  function children(int $id) {
    return $this->{$this->type()}->childrenConditional("id = ?", $id);
  }

  /**
   * Returns descendants of a node, with their depths in integer
   **/
  public function descendants(int $id): array {
    $res = $this->{$this->type()}->descendantsConditional("id = ?", /* absolute depths*/ false, $id);
    if (!is_array($res)) {
      return [];
    }
    $out = [];
    foreach ($res as $v) {
      $out[$v['title']] = $v;
    }
    return $out;
  }

  /**
   * Return depth of a node
   **/
  public function depth(int $id): int {
    return $this->{$this->type()}->depthConditional("id = ?", $id);
  }

  /**
   * Returns parent of a node
   **/
  public function parentNode(int $id) {
    return $this->{$this->type()}->parentNodeConditional("id = ?", $id);
  }

  /**
   * Reset the table back to its initial state
   * Keep in mind that this will not touch relations
   *
   * @throws Exception
   */
  function reset(bool $ensure = false): int {
    if (!$ensure) {
      throw new Exception("You must pass true to this function, otherwise it won't work.");
    }
    $res = Jf::sql("DELETE 
                    FROM ".$this->fullTable());
    $adapter = get_class(Jf::$db);
    if ($this->isMySql()) {
      Jf::sql("ALTER TABLE {$this->fullTable()} AUTO_INCREMENT = 1");
    } elseif ($this->isSQLite()) {
      Jf::sql("DELETE 
              FROM sqlite_sequence 
              WHERE name = ? ", $this->fullTable());
    } else {
      throw new Exception("Rbac can not reset table on this type of database: {$adapter}");
    }
    Jf::sql("INSERT INTO {$this->fullTable()} (title, description, lft, rght) 
            VALUES (?, ?, ?, ?)", "root", "root", 0, 1);
    return (int)$res;
  }

  /**
   * Assigns a role to a permission (or vice-verse)
   * @throws Exception
   * @todo: Implement custom error handler
   **/
  public function assign($role, $permission): bool {
    if (is_null($role) || is_null($permission)) {
      return false;
    }
    $roleID = $this->getRoleId($role);
    $permissionID = $this->getPermissionId($permission);

    return Jf::sql("INSERT INTO {$this->tablePrefix()}rolepermissions (role_id, permission_id, assignment_date)
	                  VALUES (?, ?, ?)", $roleID, $permissionID, Jf::time()) >= 1;
  }

  /**
   * Unassigned a role-permission relation
   *
   * @throws Exception
   */
  public function unassign($role, $permission): bool {
    $roleID = $this->getRoleId($role);
    $permissionID = $this->getPermissionId($permission);

    return Jf::sql("DELETE 
                    FROM {$this->tablePrefix()}rolepermissions 
                    WHERE role_id = ? AND permission_id = ?", $roleID, $permissionID) == 1;
  }

  /**
   * Remove all role-permission relations
   * mostly used for testing
   *
   * @throws Exception
   */
  public function resetAssignments(bool $ensure = false): int {
    if (!$ensure) {
      throw new Exception("You must pass true to this function, otherwise it won't work.");
    }
    $res = Jf::sql("DELETE 
                    FROM {$this->tablePrefix()}rolepermissions");

    $adapter = get_class(Jf::$db);
    if ($this->isMySql()) {
      Jf::sql("ALTER TABLE {$this->tablePrefix()}rolepermissions AUTO_INCREMENT = 1");
    } elseif ($this->isSQLite()) {
      Jf::sql("DELETE 
               FROM sqlite_sequence 
               WHERE name = ? ", $this->tablePrefix() . "_rolepermissions");
    } else {
      throw new Exception("Rbac can not reset table on this type of database: {$adapter}");
    }
    $this->assign($this->rootId(), $this->rootId());
    return $res;
  }
}
