<?php

namespace Inok\RBAC\Tests;

use ArgumentCountError;
use Exception;
use Inok\RBAC\Lib\PermissionManager;
use PHPUnit\DbUnit\DataSet\Filter;

/**
 * Unit Tests for PhpRbac PSR Wrapper
 **/
class RbacPermissionsTest extends RbacBase {
  protected function instance(): PermissionManager {
    return self::$rbac->permissions;
  }

  protected function type(): string {
    return "permissions";
  }

  /*
   * Test for proper object instantiation
   */
  public function testPermissionsInstance(): void {
    $this->assertInstanceOf(PermissionManager::class, self::$rbac->permissions);
  }

  /*
   * Tests for $this->instance()->remove()
   */
  /**
   * @throws Exception
   */
  public function testPermissionsRemoveSingle(): void {
    $permId1 = $this->instance()->add($this->type() . '_1', $this->type() . ' Description 1');

    $this->instance()->remove($permId1);

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
  public function testPermissionsRemoveSingleRole(): void {
    $permId1 = $this->instance()->add($this->type() . '_1', $this->type() . ' Description 1');
    $roleId1 = self::$rbac->roles->add('roles_1', 'roles Description 1');

    $this->instance()->assign($roleId1, $permId1);

    $this->instance()->remove($permId1);

    $dataSet = $this->getConnection()->createDataSet();

    $filterDataSet = new Filter($dataSet);
    $filterDataSet->addIncludeTables([
      $this->instance()->tablePrefix() . $this->type(),
      $this->instance()->tablePrefix() . 'rolepermissions',
      $this->instance()->tablePrefix() . 'roles',
    ]);

    $filterDataSet->setExcludeColumnsForTable(
      $this->instance()->tablePrefix() . 'rolepermissions',
      ['assignment_date']
    );

    $expectedDataSet = $this->createFlatXmlDataSet(self::$datasetPath . $this->type() . '/expected_remove_single_role.xml');

    $this->assertDataSetsEqual($expectedDataSet, $filterDataSet);
  }

  /**
   * @throws Exception
   */
  public function testPermissionsRemoveRecursive(): void {
    $permId1 = $this->instance()->add($this->type() . '_1', $this->type() . ' Description 1');
    $permId2 = $this->instance()->add($this->type() . '_2', $this->type() . ' Description 2', $permId1);
    $this->instance()->add($this->type() . '_3', $this->type() . ' Description 3', $permId1);
    $this->instance()->add($this->type() . '_4', $this->type() . ' Description 4');

    $roleId1 = self::$rbac->roles->add('roles_1', 'roles Description 1');

    $this->instance()->assign($roleId1, $permId2);

    $this->instance()->remove($permId1, true);

    $dataSet = $this->getConnection()->createDataSet();

    $filterDataSet = new Filter($dataSet);
    $filterDataSet->addIncludeTables([
      $this->instance()->tablePrefix() . $this->type(),
      $this->instance()->tablePrefix() . 'rolepermissions',
      $this->instance()->tablePrefix() . 'roles',
    ]);

    $filterDataSet->setExcludeColumnsForTable(
      $this->instance()->tablePrefix() . 'rolepermissions',
      ['assignment_date']
    );

    $expectedDataSet = $this->createFlatXmlDataSet(self::$datasetPath . $this->type() . '/expected_remove_recursive.xml');

    $this->assertDataSetsEqual($expectedDataSet, $filterDataSet);
  }

  /**
   * @throws Exception
   */
  public function testPermissionsRemoveFalse(): void {
    $result = $this->instance()->remove(5);

    $this->assertFalse($result);
  }

  /*
   * Tests for $this->instance()->roles()
   */
  /**
   * @throws Exception
   */
  public function testPermissionsRolesOnlyID(): void {
    $permId1 = $this->instance()->add($this->type() . '_1', $this->type() . ' Description 1');

    $roleId1 = self::$rbac->roles->add('roles_1', 'roles Description 1');
    $roleId2 = self::$rbac->roles->add('roles_2', 'roles Description 2');
    $roleId3 = self::$rbac->roles->add('roles_3', 'roles Description 3');

    $this->instance()->assign($roleId1, $permId1);
    $this->instance()->assign($roleId2, $permId1);
    $this->instance()->assign($roleId3, $permId1);

    $result = $this->instance()->roles($permId1);

    $expected = [2, 3, 4];

    $this->assertSame($expected, $result);
  }

  /**
   * @throws Exception
   */
  public function testPermissionsRolesBadIDNull(): void {
    $result = $this->instance()->roles(20);

    $this->assertNull($result);
  }

  /**
   * @throws Exception
   */
  public function testPermissionsRolesNotOnlyID(): void {
    self::$rbac->roles->addPath("/roles_1/roles_2");
    self::$rbac->permissions->addPath("/permissions_1/permissions_2");

    self::$rbac->assign("/roles_1/roles_2", "/permissions_1/permissions_2");

    $rolesAssigned = self::$rbac->permissions->roles('/permissions_1/permissions_2', false);

    $expected = [
      [
        'id' => 3,
        'title' => 'roles_2',
        'description' => '',
      ],
    ];

    $this->assertSame($expected, $rolesAssigned);
  }

  /**
   * @throws Exception
   */
  public function testPermissionsRolesNotOnlyIDNullBadParameters(): void {
    $rolesAssigned = self::$rbac->permissions->roles('/permissions_1/permissions_2', false);

    $this->assertSame(null, $rolesAssigned);
  }

  public function testPermissionsRolesPassNothing(): void {
    $this->expectException(ArgumentCountError::class);

    $this->instance()->roles();
  }

  /*
   * Tests for $this->instance()->unassignRoles()
   */
  /**
   * @throws Exception
   */
  public function testPermissionsUnassignRoles(): void {
    $permId1 = $this->instance()->add($this->type() . '_1', $this->type() . ' Description 1');

    $roleId1 = self::$rbac->roles->add('roles_1', 'roles Description 1');
    $roleId2 = self::$rbac->roles->add('roles_2', 'roles Description 2');
    $roleId3 = self::$rbac->roles->add('roles_3', 'roles Description 3');

    $this->instance()->assign($roleId1, $permId1);
    $this->instance()->assign($roleId2, $permId1);
    $this->instance()->assign($roleId3, $permId1);

    $this->instance()->unassignRoles($permId1);

    $dataSet = $this->getConnection()->createDataSet();

    $filterDataSet = new Filter($dataSet);
    $filterDataSet->addIncludeTables([
      $this->instance()->tablePrefix() . 'rolepermissions',
    ]);

    $filterDataSet->setExcludeColumnsForTable(
      $this->instance()->tablePrefix() . 'rolepermissions',
      ['assignment_date']
    );

    $expectedDataSet = $this->createFlatXmlDataSet(self::$datasetPath . $this->type() . '/expected_unassign_roles.xml');

    $this->assertDataSetsEqual($expectedDataSet, $filterDataSet);
  }

  /**
   * @throws Exception
   */
  public function testPermissionsUnassignRolesBadID(): void {
    $result = $this->instance()->unassignRoles(20);

    $this->assertSame(0, $result);
  }
}
