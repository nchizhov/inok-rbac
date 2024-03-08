<?php

namespace Inok\RBAC\Tests;

use ArgumentCountError;
use Exception;
use Inok\RBAC\Lib\RoleManager;
use PHPUnit\DbUnit\DataSet\Filter;

/**
 * Unit Tests for PhpRbac PSR Wrapper
 **/
class RbacRolesTest extends RbacBase {
  protected function instance(): RoleManager {
    return self::$rbac->roles;
  }

  protected function type(): string {
    return "roles";
  }

  /*
   * Test for proper object instantiation
   */
  public function testRolesInstance(): void {
    $this->assertInstanceOf(RoleManager::class, self::$rbac->roles);
  }

  /*
   * Tests for self::$rbac->Roles->permissions()
  */
  /**
   * @throws Exception
   */
  public function testRolesPermissionsIdOnly(): void {
    $permId1 = self::$rbac->permissions->add('permissions_1', 'permissions Description 1');
    $permId2 = self::$rbac->permissions->add('permissions_1', 'permissions Description 1');
    $permId3 = self::$rbac->permissions->add('permissions_1', 'permissions Description 1');

    $roleId1 = $this->instance()->add($this->type() . '_1', $this->type() . ' Description 1');

    $this->instance()->assign($roleId1, $permId1);
    $this->instance()->assign($roleId1, $permId2);
    $this->instance()->assign($roleId1, $permId3);

    $result = $this->instance()->permissions($permId1);

    $expected = [2, 3, 4];

    $this->assertSame($expected, $result);
  }

  /**
   * @throws Exception
   */
  public function testRolesPermissionsNotOnlyID(): void {
    self::$rbac->roles->addPath("/roles_1/roles_2");
    self::$rbac->permissions->addPath("/permissions_1/permissions_2");

    self::$rbac->assign("/roles_1/roles_2", "/permissions_1/permissions_2");

    $permissionsAssigned = self::$rbac->roles->permissions('/roles_1/roles_2', false);

    $expected = [
      [
        'id' => 3,
        'title' => 'permissions_2',
        'description' => '',
      ],
    ];

    $this->assertSame($expected, $permissionsAssigned);
  }

  /**
   * @throws Exception
   */
  public function testRolesPermissionsNotOnlyIDNullBadParameters(): void {
    $rolesAssigned = self::$rbac->roles->permissions('/roles_1/roles_2', false);

    $this->assertSame(null, $rolesAssigned);
  }

  public function testRolesPermissionsPassNothing(): void {
    $this->expectException(ArgumentCountError::class);

    $this->instance()->permissions();
  }

  /*
   * Tests for self::$rbac->Roles->hasPermission()
   */
  /**
   * @throws Exception
   */
  public function testRolesHasPermission(): void {
    $permId1 = self::$rbac->permissions->add('permissions_1', 'permissions Description 1');
    $roleId1 = $this->instance()->add($this->type() . '_1', $this->type() . ' Description 1');

    $this->instance()->assign($roleId1, $permId1);

    $result = self::$rbac->roles->hasPermission($roleId1, $permId1);

    $this->assertTrue($result);
  }

  /**
   * @throws Exception
   */
  public function testRolesHasPermissionFalse(): void {
    $roleId1 = $this->instance()->add($this->type() . '_1', $this->type() . ' Description 1');

    $result = self::$rbac->roles->hasPermission($roleId1, 4);

    $this->assertFalse($result);
  }

  /*
   * Tests for self::$rbac->Roles->unassignPermissions()
   */
  /**
   * @throws Exception
   */
  public function testRolesUnassignPermissions(): void {
    $roleId1 = $this->instance()->add($this->type() . '_1', $this->type() . ' Description 1');

    $permId1 = self::$rbac->permissions->add('permissions_1', 'permissions Description 1');
    $permId2 = self::$rbac->permissions->add('permissions_2', 'permissions Description 2');
    $permId3 = self::$rbac->permissions->add('permissions_3', 'permissions Description 3');

    $this->instance()->assign($roleId1, $permId1);
    $this->instance()->assign($roleId1, $permId2);
    $this->instance()->assign($roleId1, $permId3);

    $this->instance()->unassignPermissions($roleId1);

    $dataSet = $this->getConnection()->createDataSet();

    $filterDataSet = new Filter($dataSet);
    $filterDataSet->addIncludeTables([
      $this->instance()->tablePrefix() . 'rolepermissions',
    ]);

    $filterDataSet->setExcludeColumnsForTable(
      $this->instance()->tablePrefix() . 'rolepermissions',
      ['assignment_date']
    );

    $expectedDataSet = $this->createFlatXmlDataSet(self::$datasetPath . $this->type() . '/expected_unassign_permissions.xml');

    $this->assertDataSetsEqual($expectedDataSet, $filterDataSet);
  }

  /**
   * @throws Exception
   */
  public function testRolesUnassignPermissionsBadID(): void {
    $result = $this->instance()->unassignPermissions(20);

    $this->assertSame(0, $result);
  }

  /*
   * Tests for self::$rbac->Roles->unassignUsers()
   */
  /**
   * @throws Exception
   */
  public function testRolesUnassignUsers(): void {
    $roleId1 = $this->instance()->add($this->type() . '_1', $this->type() . ' Description 1');
    $roleId2 = $this->instance()->add($this->type() . '_1', $this->type() . ' Description 1');
    $roleId3 = $this->instance()->add($this->type() . '_1', $this->type() . ' Description 1');

    self::$rbac->users->assign($roleId1, 5);
    self::$rbac->users->assign($roleId2, 5);
    self::$rbac->users->assign($roleId3, 5);

    $this->instance()->unassignUsers($roleId2);

    $dataSet = $this->getConnection()->createDataSet();

    $filterDataSet = new Filter($dataSet);
    $filterDataSet->addIncludeTables([
      $this->instance()->tablePrefix() . 'userroles',
    ]);

    $filterDataSet->setExcludeColumnsForTable(
      $this->instance()->tablePrefix() . 'userroles',
      ['assignment_date']
    );

    $expectedDataSet = $this->createFlatXmlDataSet(self::$datasetPath . $this->type() . '/expected_unassign_users.xml');

    $this->assertDataSetsEqual($expectedDataSet, $filterDataSet);
  }

  /**
   * @throws Exception
   */
  public function testRolesUnassignUsersBadID(): void {
    $result = $this->instance()->unassignUsers(20);

    $this->assertSame(0, $result);
  }

  /*
   * Tests for self::$rbac->Roles->remove()
   */
  /**
   * @throws Exception
   */
  public function testRolesRemoveSingle(): void {
    $roleId1 = $this->instance()->add($this->type() . '_1', $this->type() . ' Description 1');

    $this->instance()->remove($roleId1);

    $dataSet = $this->getConnection()->createDataSet();

    $filterDataSet = new Filter($dataSet);
    $filterDataSet->addIncludeTables([
      $this->instance()->tablePrefix() . $this->type(),
    ]);

    $expectedDataSet = $this->createFlatXmlDataSet(self::$datasetPath . $this->type() . '/expected_remove_single.xml');

    $this->assertDataSetsEqual($expectedDataSet, $filterDataSet);
  }

  /**
   * @throws Exception
   */
  public function testRolesRemoveSinglePermission(): void {
    $permId1 = self::$rbac->permissions->add('permissions_1', 'permissions Description 1');
    $permId2 = self::$rbac->permissions->add('permissions_2', 'permissions Description 2');
    $permId3 = self::$rbac->permissions->add('permissions_3', 'permissions Description 3');

    $roleId1 = $this->instance()->add($this->type() . '_1', $this->type() . ' Description 1');
    $this->instance()->add($this->type() . '_2', $this->type() . ' Description 2');
    $this->instance()->add($this->type() . '_3', $this->type() . ' Description 3');

    $this->instance()->assign($roleId1, $permId1);
    $this->instance()->assign($roleId1, $permId2);
    $this->instance()->assign($roleId1, $permId3);

    self::$rbac->users->assign($roleId1, 5);

    $this->instance()->remove($roleId1);

    $dataSet = $this->getConnection()->createDataSet();

    $filterDataSet = new Filter($dataSet);
    $filterDataSet->addExcludeTables([
      $this->instance()->tablePrefix() . 'permissions',
    ]);

    $filterDataSet->setExcludeColumnsForTable(
      $this->instance()->tablePrefix() . 'rolepermissions',
      ['assignment_date']
    );

    $filterDataSet->setExcludeColumnsForTable(
      $this->instance()->tablePrefix() . 'userroles',
      ['assignment_date']
    );

    $expectedDataSet = $this->createFlatXmlDataSet(self::$datasetPath . $this->type() . '/expected_remove_single_permission.xml');

    $this->assertDataSetsEqual($expectedDataSet, $filterDataSet);
  }

  /**
   * @throws Exception
   */
  public function testRolesRemoveRecursive(): void {
    $roleId1 = $this->instance()->add($this->type() . '_1', $this->type() . ' Description 1');
    $this->instance()->add($this->type() . '_2', $this->type() . ' Description 2', $roleId1);
    $this->instance()->add($this->type() . '_3', $this->type() . ' Description 3', $roleId1);
    $this->instance()->add($this->type() . '_4', $this->type() . ' Description 4');

    $permId1 = self::$rbac->permissions->add('permissions_1', 'permissions Description 1');

    $this->instance()->assign($roleId1, $permId1);

    self::$rbac->users->assign($roleId1, 5);

    $this->instance()->remove($roleId1, true);

    $dataSet = $this->getConnection()->createDataSet();

    $filterDataSet = new Filter($dataSet);
    $filterDataSet->addIncludeTables([
      $this->instance()->tablePrefix() . 'rolepermissions',
      $this->instance()->tablePrefix() . $this->type(),
      $this->instance()->tablePrefix() . 'userroles',
    ]);

    $filterDataSet->setExcludeColumnsForTable(
      $this->instance()->tablePrefix() . 'rolepermissions',
      ['assignment_date']
    );

    $filterDataSet->setExcludeColumnsForTable(
      $this->instance()->tablePrefix() . 'userroles',
      ['assignment_date']
    );

    $expectedDataSet = $this->createFlatXmlDataSet(self::$datasetPath . $this->type() . '/expected_remove_recursive.xml');

    $this->assertDataSetsEqual($expectedDataSet, $filterDataSet);
  }

  /**
   * @throws Exception
   */
  public function testRolesRemoveFalse(): void {
    $result = $this->instance()->remove(5);

    $this->assertFalse($result);
  }
}
