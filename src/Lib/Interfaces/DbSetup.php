<?php

namespace Inok\RBAC\Lib\Interfaces;

interface DbSetup {
  public static function setup(): void;

  public static function getTables(): array;
}
