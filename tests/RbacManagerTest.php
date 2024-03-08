<?php

namespace Inok\RBAC\Tests;

use ArgumentCountError;
use Exception;
use Inok\RBAC\Lib\Exceptions\RbacUserNotProvidedException;
use PHPUnit\DbUnit\DataSet\Filter;
use TypeError;

/**
 * Unit Tests for PhpRbac PSR Wrapper
 **/
class RbacManagerTest extends RbacSetup {
  /*
   * Tests for self::$rbac->assign()
   */
  /**
   * @throws Exception
   */
  public function testManagerAssignWithId(): void {
    $permId = self::$rbac->permissions->add('permissions_1', 'permissions Description 1');
    $roleId = self::$rbac->roles->add('roles_1', 'roles Description 1');

    self::$rbac->assign($roleId, $permId);

    $dataSet = $this->getConnection()->createDataSet();

    $filterDataSet = new Filter($dataSet);
    $filterDataSet->addExcludeTables([self::$rbac->tablePrefix() . 'userroles']);
    $filterDataSet->setExcludeColumnsForTable(
      self::$rbac->tablePrefix() . 'rolepermissions',
      ['assignment_date']
    );

    $expectedDataSet = $this->createFlatXmlDataSet(self::$datasetPath . 'manager/expected_assign_id.xml');

    $this->assertDataSetsEqual($expectedDataSet, $filterDataSet);
  }

  /**
   * @throws Exception
   */
  public function testManagerAssignWithTitle(): void {
    self::$rbac->permissions->add('permissions_1', 'permissions Description 1');
    self::$rbac->roles->add('roles_1', 'roles Description 1');

    self::$rbac->assign('roles_1', 'permissions_1');

    $dataSet = $this->getConnection()->createDataSet();

    $filterDataSet = new Filter($dataSet);
    $filterDataSet->addExcludeTables([self::$rbac->tablePrefix() . 'userroles']);
    $filterDataSet->setExcludeColumnsForTable(
      self::$rbac->tablePrefix() . 'rolepermissions',
      ['assignment_date']
    );

    $expectedDataSet = $this->createFlatXmlDataSet(self::$datasetPath . 'manager/expected_assign_title.xml');

    $this->assertDataSetsEqual($expectedDataSet, $filterDataSet);
  }

  /**
   * @throws Exception
   */
  public function testManagerAssignWithPath(): void {
    self::$rbac->permissions->addPath('/permissions_1/permissions_2/permissions_3');
    self::$rbac->roles->addPath('/roles_1/roles_2/roles_3');

    self::$rbac->assign('/roles_1/roles_2', '/permissions_1/permissions_2');

    $dataSet = $this->getConnection()->createDataSet();

    $filterDataSet = new Filter($dataSet);
    $filterDataSet->addExcludeTables([self::$rbac->tablePrefix() . 'userroles']);

    $filterDataSet->setExcludeColumnsForTable(
      self::$rbac->tablePrefix() . 'rolepermissions',
      ['assignment_date']
    );

    $expectedDataSet = $this->createFlatXmlDataSet(self::$datasetPath . 'manager/expected_assign_path.xml');

    $this->assertDataSetsEqual($expectedDataSet, $filterDataSet);
  }

  /**
   * @throws Exception
   */
  public function testManagerAssignWithNullRoleNullPermFalse(): void {
    $return = self::$rbac->assign(null, null);

    $this->assertFalse($return);
  }

  public function testManagerAssignWithNullRoleNoPermError(): void {
    $this->expectException(ArgumentCountError::class);

    self::$rbac->assign(null);
  }

  public function testManagerAssignWithNoParametersError(): void {
    $this->expectException(ArgumentCountError::class);

    self::$rbac->assign(null);
  }

  /*
   * Tests for self::$rbac->check()
   */
  /**
   * @throws Exception
   */
  public function testManagerCheckId(): void {
    $roleId1 = self::$rbac->roles->add('roles_1', 'roles Description 1');
    $permId1 = self::$rbac->permissions->add('permissions_1', 'permissions Description 1');

    self::$rbac->roles->assign($roleId1, $permId1);
    self::$rbac->users->assign($roleId1, 5);

    $result = self::$rbac->check($permId1, 5);

    $this->assertTrue($result);
  }

  /**
   * @throws Exception
   */
  public function testManagerCheckTitle(): void {
    $roleId1 = self::$rbac->roles->add('roles_1', 'roles Description 1');
    $permId1 = self::$rbac->permissions->add('permissions_1', 'permissions Description 1');

    self::$rbac->roles->assign($roleId1, $permId1);
    self::$rbac->users->assign($roleId1, 5);

    $result = self::$rbac->check('permissions_1', 5);

    $this->assertTrue($result);
  }

  /**
   * @throws Exception
   */
  public function testManagerCheckPath(): void {
    self::$rbac->permissions->addPath('/permissions_1/permissions_2/permissions_3');
    self::$rbac->permissions->pathId('/permissions_1/permissions_2/permissions_3');

    self::$rbac->roles->addPath('/roles_1/roles_2/roles_3');
    $roleId1 = self::$rbac->roles->pathId('/roles_1/roles_2/roles_3');

    self::$rbac->roles->assign($roleId1, 3);
    self::$rbac->users->assign($roleId1, 5);

    $result = self::$rbac->check('/permissions_1/permissions_2', 5);

    $this->assertTrue($result);
  }

  /**
   * @throws Exception
   */
  public function testManagerCheckBadPermBadUserFalse(): void {
    $result = self::$rbac->check(5, 5);

    $this->assertFalse($result);
  }

  public function testManagerCheckWithNullUserIdException(): void {
    $this->expectException(RbacUserNotProvidedException::class);

    self::$rbac->check(5, null);
  }

  public function testManagerCheckWithNullPermException(): void {
    $this->expectException(TypeError::class);

    $permId = self::$rbac->permissions->add('permissions_1', 'permissions Description 1');
    self::$rbac->check(null, $permId);
  }

  public function testManagerCheckWithNoUserIdException(): void {
    $this->expectException(ArgumentCountError::class);

    self::$rbac->check(5);
  }

  /*
   * Tests for self::$rbac->enforce()
   */
  /**
   * @throws Exception
   */
  public function testManagerEnforceId(): void {
    $roleId1 = self::$rbac->roles->add('roles_1', 'roles Description 1');
    $permId1 = self::$rbac->permissions->add('permissions_1', 'permissions Description 1');

    self::$rbac->roles->assign($roleId1, $permId1);
    self::$rbac->users->assign($roleId1, 5);

    $result = self::$rbac->enforce($permId1, 5);

    $this->assertTrue($result);
  }

  /**
   * @throws Exception
   */
  public function testManagerEnforceTitle(): void {
    $roleId1 = self::$rbac->roles->add('roles_1', 'roles Description 1');
    $permId1 = self::$rbac->permissions->add('permissions_1', 'permissions Description 1');

    self::$rbac->roles->assign($roleId1, $permId1);
    self::$rbac->users->assign($roleId1, 5);

    $result = self::$rbac->enforce('permissions_1', 5);

    $this->assertTrue($result);
  }

  /**
   * @throws Exception
   */
  public function testManagerEnforcePath(): void {
    self::$rbac->permissions->addPath('/permissions_1/permissions_2/permissions_3');
    self::$rbac->permissions->pathId('/permissions_1/permissions_2/permissions_3');

    self::$rbac->roles->addPath('/roles_1/roles_2/roles_3');
    $roleId1 = self::$rbac->roles->pathId('/roles_1/roles_2/roles_3');

    self::$rbac->roles->assign($roleId1, 3);
    self::$rbac->users->assign($roleId1, 5);

    $result = self::$rbac->enforce('/permissions_1/permissions_2', 5);

    $this->assertTrue($result);
  }

  public function testManagerEnforceWithNullUserIdException(): void {
    $this->expectException(RbacUserNotProvidedException::class);

    self::$rbac->enforce(5, null);
  }

  public function testManagerEnforceWithNullPermException(): void {
    $this->expectException(TypeError::class);

    $permId = self::$rbac->permissions->add('permissions_1', 'permissions Description 1');
    self::$rbac->enforce(null, $permId);
  }

  public function testManagerEnforceWithNoUserIdException(): void {
    $this->expectException(ArgumentCountError::class);

    self::$rbac->enforce(5);
  }

  /*
   * Tests for self::$rbac->reset()
   */
  /**
   * @throws Exception
   */
  public function testManagerReset(): void {
    $roleId1 = self::$rbac->roles->add('roles_1', 'roles Description 1');
    $permId1 = self::$rbac->permissions->add('permissions_1', 'permissions Description 1');

    self::$rbac->roles->assign($roleId1, $permId1);
    self::$rbac->users->assign($roleId1, 5);

    self::$rbac->reset(true);

    $dataSet = $this->getConnection()->createDataSet();

    $filterDataSet = new Filter($dataSet);

    $filterDataSet->setExcludeColumnsForTable(
      self::$rbac->tablePrefix() . 'rolepermissions',
      ['assignment_date']
    );

    $filterDataSet->setExcludeColumnsForTable(
      self::$rbac->tablePrefix() . 'userroles',
      ['assignment_date']
    );

    $expectedDataSet = $this->createFlatXmlDataSet(self::$datasetPath . 'manager/expected_reset.xml');

    $this->assertDataSetsEqual($expectedDataSet, $filterDataSet);
  }

  public function testManagerResetFalseException() {
    $this->expectException(Exception::class);

    self::$rbac->reset();
  }
}
