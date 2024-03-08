<?php

namespace Inok\RBAC\Tests;

use Exception;
use Inok\RBAC\Lib\Exceptions\RbacUnsupportedDriverException;
use Inok\RBAC\Lib\Jf;
use Inok\RBAC\Rbac;

/**
 * Unit Tests for PhpRbac PSR Wrapper
 **/
class RbacSetup extends Generic_Tests_DatabaseTestCase {
  /*
   * Test Setup and Fixture
   */
  public static ?Rbac $rbac;
  protected static ?string $datasetPath;

  /**
   * @throws Exception
   */
  public static function setUpBeforeClass(): void {
    self::$datasetPath = dirname(__FILE__).DIRECTORY_SEPARATOR.'datasets'.DIRECTORY_SEPARATOR;
    if (Jf::$dbDriver === 'sqlite') {
      self::$rbac->reset(true);
    }
  }

  /**
   * @throws RbacUnsupportedDriverException
   */
  protected function setUp(): void {
    self::$rbac = new Rbac($this->getConnection()->getConnection());
    parent::setUp();
  }

  /**
   * @throws Exception
   */
  protected function tearDown(): void {
    if (Jf::$dbDriver === 'sqlite') {
      self::$rbac->reset(true);
    }
  }
}
