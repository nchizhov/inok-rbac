<?php

namespace Inok\RBAC\Lib\Interfaces;

// TODO: Для функций параметры
interface NestedSetInterface {
  public function insertChild(int $pid = 0);

  public function insertSibling(int $id = 0): int;

  public function deleteSubtree(int $id): int;

  public function delete(int $id): int;

  //function Move($ID,$NewPID);
  //function Copy($ID,$NewPID);

  public function fullTree();

  public function children(int $id);

  public function descendants(int $id, bool $absoluteDepths = false);

  public function leaves(?int $pid = null);

  public function path(int $id);

  public function depth(int $id): int;

  public function parentNode(int $id): ?array;

  public function sibling(int $id, int $siblingDistance = 1): ?array;
}
