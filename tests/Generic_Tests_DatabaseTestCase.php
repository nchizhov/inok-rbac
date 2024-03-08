<?php

namespace Inok\RBAC\Tests;
use Inok\RBAC\Lib\Jf;
use PDO;
use PHPUnit\DbUnit\DataSet\XmlDataSet;
use PHPUnit\DbUnit\Database\Connection;
use PHPUnit\DbUnit\TestCase;

/**
 * Unit Tests for PhpRbac PSR Wrapper
 **/
abstract class Generic_Tests_DatabaseTestCase extends TestCase {

  // only instantiate pdo once for test clean-up/fixture load
  static private ?PDO $pdo = null;

  // only instantiate PHPUnit_Extensions_Database_DB_IDatabaseConnection once per test
  private ?Connection $conn = null;

  final public function getConnection(): Connection {
    if ($this->conn !== null) {
      return $this->conn;
    }
    if (self::$pdo === null) {
      self::$pdo = new PDO($GLOBALS['DB_DSN'], $GLOBALS['DB_USER'], $GLOBALS['DB_PASSWD']);
    }
    $this->conn = $this->createDefaultDBConnection(self::$pdo, $GLOBALS['DB_DBNAME']);
    return $this->conn;
  }

  public function getDataSet(): XmlDataSet {
    return $this->createXMLDataSet(dirname(__FILE__) . '/datasets/database-seed.xml');
  }
}

register_shutdown_function(function(): void {
  if (Jf::$dbDriver !== 'sqlite') {
    return;
  }
  $files = glob(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'phprbac.sqlite3*');
  foreach ($files as $file) {
    if (is_file($file)) {
      unlink($file);
    }
  }
});
