<?php

namespace Inok\RBAC\Lib\Nestedset;

use Exception;
use Inok\RBAC\Lib\Base;
use Inok\RBAC\Lib\Interfaces\ExtendedNestedSet;
use Inok\RBAC\Lib\Jf;

/**
 * FullNestedSet Class
 * This class provides a means to implement Hierarchical data in flat SQL tables.
 * Queries extracted from http://dev.mysql.com/tech-resources/articles/hierarchical-data.html
 * Tested and working properly.
 *
 * Usage:
 * have a table with at least 3 INT fields for id, left and right.
 * Create a new instance of this class and pass the name of table and name of the 3 fields above
 */
class FullNestedSet extends BaseNestedSet implements ExtendedNestedSet {
  /**
   * Returns the ID of a node based on a SQL conditional string
   * It accepts other params in the PreparedStatements format
   **/
  public function getID(string $conditionString, ?string $rest = null): ?int {
    $args = func_get_args();
    array_shift($args);
    $query = "SELECT id AS id 
              FROM {$this->table()} 
              WHERE ".$conditionString."  
              LIMIT 1";
    array_unshift($args, $query);
    $res = call_user_func_array([Jf::class, 'sql'], $args);
    return ($res) ?$res[0]['id'] : null;
  }

  /**
   * Returns the record of a node based on a SQL conditional string
   * It accepts other params in the PreparedStatements format
   **/
  public function getRecord(string $conditionString, ?string $rest = null): ?array {
    $args = func_get_args();
    array_shift($args);
    $query = "SELECT id, lft, rght, title, description 
              FROM {$this->table()} 
              WHERE ".$conditionString;
    array_unshift($args, $query);
    $res = call_user_func_array([Jf::class, 'sql'], $args);
    return ($res) ? $res[0] : null;
  }

  /**
   * Returns the depth of a node in the tree
   * Note: this uses path
   **/
  public function depthConditional(string $conditionString, ?string $rest = null): int {
    $arguments = func_get_args();
    $path = call_user_func_array([$this, "pathConditional"], $arguments);
    if (is_null($path)) {
      $path = 0;
    }
    return count($path) - 1;
  }

  /**
   * Returns a sibling of the current node
   * Note: You can't find siblings of roots
   * Note: this is a heavy function on nested sets, uses both children (which is quite heavy) and path
   *
   * @throws Exception
   */
  public function siblingConditional(string $conditionString, int $siblingDistance = 1, ?string $rest = null): ?array {
    $arguments = func_get_args();
    array_shift($arguments); //Rid $siblingDistance
    $parent = call_user_func_array([$this, "parentNodeConditional"], $arguments);
    $siblings = $this->children($parent['id']);
    if (!$siblings) {
      return null;
    }
    $n = 0;
    $id = call_user_func_array([$this, "getID"], $arguments);
    foreach ($siblings as $sibling) {
      if ($sibling['id'] == $id) {
        break;
      }
      $n++;
    }
    return $siblings[$n + $siblingDistance];
  }

  /**
   * Returns the parent of a node
   * Note: this uses path
   **/
  public function parentNodeConditional(string $conditionString, ?string $rest = null): ?array {
    $arguments = func_get_args();
    $path = call_user_func_array([$this, "pathConditional"], $arguments);
    if (count($path) < 2) {
      return null;
    }
    return $path[count($path) - 2];
  }

  /**
   * Deletes a node and shifts the children up
   * Note: use a condition to support only 1 row, LIMIT 1 used.
   *
   * @throws Exception
   */
  public function deleteConditional(string $conditionString, ?string $rest = null): bool {
    $arguments = func_get_args();
    array_shift($arguments);
    $query = "SELECT lft AS `left`, rght AS `right`
			        FROM {$this->table()}
			        WHERE ".$conditionString."  
			        LIMIT 1";

    array_unshift($arguments, $query);
    $info = call_user_func_array([Jf::class, 'sql'], $arguments);
    if (!$info) {
      return false;
    }
    $info = $info[0];

    $count = Jf::sql("DELETE 
                      FROM {$this->table()} 
                      WHERE lft = ?", $info["left"]);

    Jf::sql("UPDATE {$this->table()} 
             SET rght = rght - 1, lft = lft - 1 
             WHERE lft BETWEEN ? AND ?", $info["left"], $info["right"]);
    Jf::sql("UPDATE {$this->table()} 
             SET rght = rght - 2 
             WHERE rght > ?", $info["right"]);
    Jf::sql("UPDATE {$this->table()} 
             SET lft = lft - 2 
             WHERE lft > ?", $info["right"]);
    return $count == 1;
  }

  /**
   * Deletes a node and all its descendants
   *
   * @throws Exception
   */
  public function deleteSubtreeConditional(string $conditionString, ?string $rest = null): bool {
    $arguments = func_get_args();
    array_shift($arguments);
    $query = "SELECT lft AS `left`, rght AS `right`, rght - lft + 1 AS width
			        FROM {$this->table()}
			        WHERE ".$conditionString;

    array_unshift($arguments, $query);
    $info = call_user_func_array([Jf::class, 'sql'], $arguments);

    $count = $this->deleteCount($info[0]);

    return $count >= 1;
  }

  /**
   * Returns all descendants of a node
   * Note: use only a single condition here
   **/
  public function descendantsConditional(string $conditionString, bool $absoluteDepths = false, ?string $rest = null): ?array {
    $depthConcat = $absoluteDepths ? "" : " - (sub_tree.innerDepth)";
    $arguments = func_get_args();
    array_shift($arguments);
    array_shift($arguments); //second argument, $AbsoluteDepths
    $query = "SELECT node.id, node.lft, node.rght, node.title, node.description, 
                     (COUNT(parent.id) - 1 ".$depthConcat.") AS depth
              FROM {$this->table()} AS node,
            	     {$this->table()} AS parent,
            	     {$this->table()} AS sub_parent,
            	     ( SELECT node.id, (COUNT(parent.id) - 1) AS innerDepth
            		     FROM {$this->table()} AS node,
            		          {$this->table()} AS parent
            		     WHERE node.lft BETWEEN parent.lft AND parent.rght
            		       AND (node.".$conditionString.")
            		     GROUP BY node.id
            		     ORDER BY node.lft
            	     ) AS sub_tree
              WHERE node.lft BETWEEN parent.lft AND parent.rght
            	  AND node.lft BETWEEN sub_parent.lft AND sub_parent.rght
            	  AND sub_parent.id = sub_tree.id
              GROUP BY node.id
              HAVING depth > 0
              ORDER BY node.lft";

    array_unshift($arguments, $query);
    $res = call_user_func_array([Jf::class, 'sql'], $arguments);
    return ($res) ? Base::mapSqlData($res, ['id', 'lft', 'rght', 'depth']) : null;
  }

  /**
   * Returns immediate children of a node
   * Note: this function performs the same as descendants but only returns results with Depth=1
   * Note: use only a single condition here
   **/
  public function childrenConditional(string $conditionString, ?string $rest = null): ?array {
    $arguments = func_get_args();
    array_shift($arguments);
    $query = "SELECT node.id, node.lft, node.rght, node.title, node.description, 
                (COUNT(parent.id) - 1 - (sub_tree.innerDepth)) AS depth
              FROM {$this->table()} AS node,
            	     {$this->table()} AS parent,
            	     {$this->table()} AS sub_parent,
           	       ( SELECT node.id, (COUNT(parent.id) - 1) AS innerDepth
            		     FROM {$this->table()} AS node,
            		          {$this->table()} AS parent
            		     WHERE node.lft BETWEEN parent.lft AND parent.rght
            		       AND (node.".$conditionString.")
            		     GROUP BY node.id
            		     ORDER BY node.lft
                   ) AS sub_tree
              WHERE node.lft BETWEEN parent.lft AND parent.rght
            	  AND node.lft BETWEEN sub_parent.lft AND sub_parent.rght
            	  AND sub_parent.id = sub_tree.id
              GROUP BY node.id
              HAVING depth = 1
              ORDER BY node.lft";

    array_unshift($arguments, $query);
    $res = call_user_func_array([Jf::class, 'sql'], $arguments);
    if ($res) {
      foreach ($res as &$v) {
        unset($v["depth"]);
      }
      $res = Base::mapSqlData($res, ['id', 'lft', 'rght']);
    }
    return $res;
  }

  /**
   * Returns the path to a node, including the node
   * Note: use a single condition, or supply "node." before condition fields.
   **/
  public function pathConditional(string $conditionString, ?string $rest = null): array {
    $arguments = func_get_args();
    array_shift($arguments);
    $query = "SELECT parent.id, parent.lft, parent.rght, parent.title, parent.description
              FROM {$this->table()} AS node,
                   {$this->table()} AS parent
              WHERE node.lft BETWEEN parent.lft AND parent.rght
                AND (node.".$conditionString.")
              ORDER BY parent.lft";

    array_unshift($arguments, $query);
    $res = call_user_func_array([Jf::class, 'sql'], $arguments);
    return is_null($res) ? [] : Base::mapSqlData($res, ['id', 'lft', 'rght']);
  }

  /**
   * Finds all leaves of a parent
   *  Note: if you don't specify $pid, There would be one less AND in the SQL Query
   *
   * @throws Exception
   */
  public function leavesConditional(?string $conditionString = null, ?string $rest = null) {
    if (is_null($conditionString)) {
      return Jf::sql("SELECT id, lft, rght, title, description
                      FROM {$this->table()}
                      WHERE rght = lft + 1");
    }
    $arguments = func_get_args();
    array_shift($arguments);
    $conditionString = "WHERE ".$conditionString;

    $query = "SELECT id, lft, rght, title, description
              FROM {$this->table()}
              WHERE rght = lft + 1
                AND lft BETWEEN
                    (SELECT lft 
                     FROM {$this->table()} ".$conditionString.")
              	AND
                    (SELECT rght 
                     FROM {$this->table()} ".$conditionString.")";

    $arguments = array_merge($arguments, $arguments);
    array_unshift($arguments, $query);
    return call_user_func_array([Jf::class, 'sql'], $arguments);
  }

  /**
   * Adds a sibling after a node
   *
   * @throws Exception
   */
  public function insertSiblingData(array $fieldValueArray = [], ?string $conditionString = null, ?string $rest = null) {
    //Find the Sibling
    $arguments = func_get_args();
    array_shift($arguments); //first argument, the array
    array_shift($arguments);
    $conditionString = (is_null($conditionString)) ? "" : "WHERE ".$conditionString;
    $query = "SELECT rght AS `right` 
              FROM {$this->table()} ".$conditionString;

    array_unshift($arguments, $query);
    $sibl = call_user_func_array([Jf::class, 'sql'], $arguments);

    $sibl = $sibl[0];
    if ($sibl == null) {
      $sibl["left"] = $sibl["right"] = 0;
    }
    Jf::sql("UPDATE {$this->table()} 
             SET rght = rght + 2 
             WHERE rght > ?", $sibl["right"]);
    Jf::sql("UPDATE {$this->table()} 
             SET lft = lft + 2 
             WHERE lft > ?", $sibl["right"]);

    $data = $this->formatInsertChildData($fieldValueArray);

    $query = "INSERT INTO {$this->table()} (lft, rght ".$data['fields'].") 
              VALUES(?, ? ".$data['values'].")";
    array_unshift($data['data'], $sibl["right"] + 2);
    array_unshift($data['data'], $sibl["right"] + 1);
    array_unshift($data['data'], $query);

    return call_user_func_array([Jf::class, 'sql'], $data['data']);
  }

  /**
   * Adds a child to the beginning of a node's children
   *
   * @throws Exception
   */
  public function insertChildData(array $fieldValueArray = [], ?string $conditionString = null, ?string $rest = null) {
    //Find the Sibling
    $arguments = func_get_args();
    array_shift($arguments); //first argument, the array
    array_shift($arguments);
    $conditionString = (is_null($conditionString)) ? "" : "WHERE ".$conditionString;
    $query = "SELECT rght AS `right`, lft AS `left` 
              FROM {$this->table()} ".$conditionString;
    array_unshift($arguments, $query);
    $parent = call_user_func_array([Jf::class, 'sql'], $arguments);

    $parent = $parent[0];
    if ($parent == null) {
      $parent["left"] = $parent["right"] = 0;
    }
    Jf::sql("UPDATE {$this->table()} 
             SET rght = rght + 2 
             WHERE rght >= ?", $parent["right"]);
    Jf::sql("UPDATE {$this->table()} 
             SET lft = lft + 2 
             WHERE lft > ?", $parent["right"]);

    $data = $this->formatInsertChildData($fieldValueArray);

    $query = "INSERT INTO {$this->table()} (lft, rght ".$data['fields'].") 
              VALUES(?, ? ".$data['values'].")";
    array_unshift($data['data'], $parent["right"] + 1);
    array_unshift($data['data'], $parent["right"]);
    array_unshift($data['data'], $query);
    return call_user_func_array([Jf::class, 'sql'], $data['data']);
  }

  /**
   * Edits a node
   **/
  public function editData(array $fieldValueArray = [], ?string $conditionString = null, ?string $rest = null) {
    //Find the Sibling
    $arguments = func_get_args();
    array_shift($arguments); //first argument, the array
    array_shift($arguments);
    $conditionString = (is_null($conditionString)) ? "" : "WHERE ".$conditionString;

    $fieldsString = "";
    $values = [];
    if (!empty($fieldValueArray)) {
      $values = array_values($fieldValueArray);
      $fieldsStringTmp = [];
      foreach ($fieldValueArray as $k => $v) {
        $fieldsStringTmp[] = "`".$k."` = ?";
      }
      $fieldsString = join(', ', $fieldsStringTmp);
    }
    $query = "UPDATE {$this->table()} 
              SET ".$fieldsString." "."$conditionString";

    array_unshift($values, $query);
    $arguments = array_merge($values, $arguments);

    return call_user_func_array([Jf::class, 'sql'], $arguments);
  }

  private function formatInsertChildData(array $data): array {
    $returnData = ['fields' => "",
                   'values' => "",
                   'data' => []];
    if (empty($data)) {
      return $returnData;
    }
    $returnData['fields'] = ", `".join('`, `', array_keys($data))."`";
    $returnData['values'] = str_repeat(', ?', count($data));
    $returnData['data'] = array_values($data);
    return $returnData;
  }
}
