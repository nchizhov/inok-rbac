<?php

namespace Inok\RBAC\Lib\Interfaces;

interface ExtendedNestedSet extends NestedSetInterface {
  public function getID(string $conditionString): ?int;

  public function insertChildData(array $fieldValueArray = [], ?string $conditionString = null);

  public function insertSiblingData(array $fieldValueArray = [], ?string $conditionString = null);

  public function deleteSubtreeConditional(string $conditionString): bool;

  public function deleteConditional(string $conditionString): bool;

  public function childrenConditional(string $conditionString);

  public function descendantsConditional(string $conditionString, bool $absoluteDepths = false);

  public function leavesConditional(?string $conditionString = null);

  public function pathConditional(string $conditionString);

  public function depthConditional(string $conditionString): int;

  public function parentNodeConditional(string $conditionString): ?array;

  public function siblingConditional(string $conditionString, int $siblingDistance = 1): ?array;
}
