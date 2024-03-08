<?php

namespace Inok\RBAC\Lib\Nestedset;

use Exception;
use Inok\RBAC\Lib\Interfaces\NestedSetInterface;
use Inok\RBAC\Lib\Jf;

/**
 * BaseNestedSet Class
 * This class provides a means to implement Hierarchical data in flat SQL tables.
 * Queries extracted from http://dev.mysql.com/tech-resources/articles/hierarchical-data.html
 *
 * Tested and working properly
 *
 * Usage:
 * have a table with at least 3 INT fields for id, left and right.
 * Create a new instance of this class and pass the name of table and name of the 3 fields above
 */
class BaseNestedSet implements NestedSetInterface {
  private string $table;

  public function __construct(string $table) {
    $this->table = $table;
  }

  protected function table(): string {
    return $this->table;
  }

  /**
   * Returns number of descendants
   *
   * @throws Exception
   */
  public function descendantCount(int $id): int {
    $res = Jf::sql("SELECT (rght - lft - 1) / 2 AS `count` 
                    FROM {$this->table()} 
                    WHERE id = ?", $id);
    return sprintf("%d", $res[0]["count"]) * 1;
  }

  /**
   * Returns the depth of a node in the tree
   * Note: this uses path
   *
   * @throws Exception
   */
  public function depth(int $id): int {
    return count($this->path($id)) - 1;
  }

  /**
   * Returns a sibling of the current node
   * Note: You can't find siblings of roots
   * Note: this is a heavy function on nested sets, uses both children (which is quite heavy) and path
   *
   * @throws Exception
   */
  public function sibling(int $id, int $siblingDistance = 1): ?array {
    $parent = $this->parentNode($id);
    $siblings = $this->children($parent['id']);
    if (!$siblings) {
      return null;
    }
    $n = 0;
    foreach ($siblings as &$sibling) {
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
   *
   * @throws Exception
   */
  public function parentNode(int $id): ?array {
    $path = $this->path($id);
    if (count($path) < 2) {
      return null;
    }
    return $path[count($path) - 2];
  }

  /**
   * Deletes a node and shifts the children up
   *
   * @throws Exception
   */
  public function delete(int $id): int {
    $info = Jf::sql("SELECT lft AS `left`, rght AS `right` 
			               FROM {$this->table()}
			               WHERE id = ?", $id);
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
    return $count;
  }

  /**
   * Deletes a node and all its descendants
   *
   * @throws Exception
   */
  public function deleteSubtree(int $id): int {
    $info = Jf::sql("SELECT lft AS `left`, rght AS `right`, (rght - lft + 1) AS width
			               FROM {$this->table()}
			               WHERE id = ?", $id);

    return $this->deleteCount($info[0]);
  }

  /**
   * Returns all descendants of a node
   *
   * @throws Exception
   */
  public function descendants(int $id, bool $absoluteDepths = false) {
    $depthConcat = $absoluteDepths ? "" : " - (sub_tree.depth)";
    return Jf::sql("SELECT node.id, node.lft, node.rght, node.title, node.description, 
                        (COUNT(parent.id) - 1 $depthConcat) AS depth
                    FROM {$this->table()} AS node,
            	           {$this->table()} AS parent,
            	           {$this->table()} AS sub_parent,
            	      (
            		        SELECT node.id, (COUNT(parent.id) - 1) AS depth
            		        FROM {$this->table()} AS node,
            		             {$this->table()} AS parent
            		        WHERE node.lft BETWEEN parent.lft AND parent.rght
            		          AND node.id = ?
            		        GROUP BY node.id
            		        ORDER BY node.lft
            	      ) AS sub_tree
                    WHERE node.lft BETWEEN parent.lft AND parent.rght
            	        AND node.lft BETWEEN sub_parent.lft AND sub_parent.rght
            	        AND sub_parent.id = sub_tree.id
                    GROUP BY node.id
                    HAVING depth > 0
                    ORDER BY node.lft", $id);
  }

  /**
   * Returns immediate children of a node
   * Note: this function performs the same as descendants but only returns results with depth=1
   *
   * @throws Exception
   */
  public function children(int $id) {
    $res = Jf::sql("SELECT node.id, node.lft, node.rght, node.title, node.description, 
                        (COUNT(parent.id) - 1 - (sub_tree.depth)) AS depth
                    FROM {$this->table()} AS node,
            	           {$this->table()} AS parent,
            	           {$this->table()} AS sub_parent,
           	            (
            		            SELECT node.id, (COUNT(parent.id) - 1) AS depth
            		            FROM {$this->table()} AS node,
            		                 {$this->table()} AS parent
            		            WHERE node.lft BETWEEN parent.lft AND parent.rght
            		              AND node.id = ?
            		            GROUP BY node.id
            		            ORDER BY node.lft
                        ) AS sub_tree
                    WHERE node.lft BETWEEN parent.lft AND parent.rght
            	        AND node.lft BETWEEN sub_parent.lft AND sub_parent.rght
            	        AND sub_parent.id = sub_tree.id
                    GROUP BY node.id
                    HAVING depth = 1
                    ORDER BY node.lft", $id);
    if ($res) {
      foreach ($res as &$v) {
        unset($v["depth"]);
      }
    }
    return $res;
  }

  /**
   * Returns the path to a node, including the node
   *
   * @throws Exception
   */
  public function path(int $id) {
    return Jf::sql("SELECT parent.id, parent.lft, parent.rght, parent.title, parent.description 
                    FROM {$this->table()} AS node, 
                         {$this->table()} AS parent
                    WHERE node.lft BETWEEN parent.lft AND parent.rght
                      AND node.id = ?
                    ORDER BY parent.lft", $id);
  }

  /**
   * Finds all leaves of a parent
   *  Note: if you don't specify $pid, There would be one less AND in the SQL Query
   *
   * @throws Exception
   */
  public function leaves(?int $pid = null) {
    if ($pid) {
      return Jf::sql("SELECT id, lft, rght, title, description
                      FROM {$this->table()}
                      WHERE rght = lft + 1 
        	              AND lft BETWEEN 
                        (SELECT lft 
                         FROM {$this->table()} 
                         WHERE id = ?)
            	          AND 
                        (SELECT rght 
                         FROM {$this->table()} 
                         WHERE id = ?)", $pid, $pid);
    }
    return Jf::sql("SELECT id, lft, rght, title, description
                    FROM {$this->table()}
                    WHERE rght = lft + 1");
  }

  /**
   * Adds a sibling after a node
   *
   * @throws Exception
   */
  public function insertSibling(int $id = 0): int {
    //Find the Sibling
    $sibl = Jf::sql("SELECT rght AS `right`  
                     FROM {$this->table()} 
                     WHERE id = ?", $id);
    $sibl = $sibl[0];
    if ($sibl == null) {
      $sibl["right"] = 0;
    }
    Jf::sql("UPDATE {$this->table()} 
             SET rght = rght + 2 
             WHERE rght > ?", $sibl["right"]);
    Jf::sql("UPDATE {$this->table()} 
             SET lft = lft + 2 
             WHERE lft > ?", $sibl["right"]);
    return Jf::sql("INSERT INTO {$this->table()} (lft, rght) 
                    VALUES(?, ?)", $sibl["right"] + 1, $sibl["right"] + 2);
  }

  /**
   * Adds a child to the beginning of a node's children
   *
   * @throws Exception
   */
  public function insertChild(int $pid = 0) {
    //Find the Sibling
    $sibl = Jf::sql("SELECT lft AS `left` 
                     FROM {$this->table()} 
                     WHERE id = ?", $pid);
    $sibl = $sibl[0];
    if ($sibl == null) {
      $sibl["left"] = 0;
    }
    Jf::sql("UPDATE {$this->table()} 
             SET rght = rght + 2 
             WHERE rght > ?", $sibl["left"]);
    Jf::sql("UPDATE {$this->table()} 
             SET lft = lft + 2 
             WHERE lft > ?", $sibl["left"]);
    return Jf::sql("INSERT INTO {$this->table()} (lft, rght) 
                    VALUES(?, ?)", $sibl["left"] + 1, $sibl["left"] + 2);
  }

  /**
   * Retrieves the full tree including depth field.
   *
   * @throws Exception
   */
  public function fullTree() {
    return Jf::sql("SELECT node.id, node.lft, node.rght, node.title, node.description, 
                        (COUNT(parent.id) - 1) AS depth
                    FROM {$this->table()} AS node,
                         {$this->table()} AS parent
                    WHERE node.lft BETWEEN parent.lft AND parent.rght
                    GROUP BY node.id
                    ORDER BY node.lft");
  }

  /**
   * @throws Exception
   */
  protected function deleteCount(array $info): int {
    $count = Jf::sql("DELETE 
                     FROM {$this->table()} 
                     WHERE lft BETWEEN ? AND ?", $info["left"], $info["right"]);
    Jf::sql("UPDATE {$this->table()} 
             SET rght = rght - ? 
             WHERE rght > ?", $info["width"], $info["right"]);
    Jf::sql("UPDATE {$this->table()} 
             SET lft = lft - ? 
             WHERE lft > ?", $info["width"], $info["right"]);
    return $count;
  }
}
