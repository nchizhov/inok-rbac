<?php

namespace Inok\RBAC\Lib;

class JModel {
  public function tablePrefix(): string {
    return Jf::tablePrefix();
  }

  protected function isSQLite(): bool {
    return Jf::$dbDriver == "sqlite";
  }

  protected function isMySql(): bool {
    return Jf::$dbDriver == "mysql";
  }
}
