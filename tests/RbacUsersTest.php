<?php

namespace Inok\RBAC\Tests;

use ArgumentCountError;
use Exception;
use Inok\RBAC\Lib\Exceptions\RbacUserNotProvidedException;
use Inok\RBAC\Lib\RbacUserManager;
use PHPUnit\DbUnit\DataSet\Filter;

/**
 * Unit Tests for PhpRbac PSR Wrapper
 **/
class RbacUsersTest extends RbacSetup {
  /*
   * Test for proper object instantiation
   */
  public function testUsersInstance(): void {
    $this->assertInstanceOf(RbacUserManager::class, self::$rbac->users);
  }

  /*
   * Tests for self::$rbac->Users->assign()
   */
  /**
   * @throws Exception
   */
  public function testUsersAssignWithId(): void {
    $roleId = self::$rbac->roles->add('roles_1', 'roles Description 1');

    self::$rbac->users->assign($roleId, 5);

    $dataSet = $this->getConnection()->createDataSet();

    $filterDataSet = new Filter($dataSet);
    $filterDataSet->addIncludeTables([
      self::$rbac->users->tablePrefix() . 'userroles',
    ]);

    $filterDataSet->setExcludeColumnsForTable(
      self::$rbac->users->tablePrefix() . 'userroles',
      ['assignment_date']
    );

    $expectedDataSet = $this->createFlatXmlDataSet(self::$datasetPath . 'users/expected_assign_with_id.xml');

    $this->assertDataSetsEqual($expectedDataSet, $filterDataSet);
  }

  /**
   * @throws RbacUserNotProvidedException
   */
  public function testUsersAssignWithIdDouble(): void {
    $roleId = self::$rbac->roles->add('roles_1', 'roles Description 1');

    self::$rbac->users->assign($roleId, 5);
    self::$rbac->users->assign($roleId, 5);

    $dataSet = $this->getConnection()->createDataSet();

    $filterDataSet = new Filter($dataSet);
    $filterDataSet->addIncludeTables([
      self::$rbac->users->tablePrefix() . 'userroles',
    ]);

    $filterDataSet->setExcludeColumnsForTable(
      self::$rbac->users->tablePrefix() . 'userroles',
      ['assignment_date']
    );

    $expectedDataSet = $this->createFlatXmlDataSet(self::$datasetPath . 'users/expected_assign_with_id.xml');

    $this->assertDataSetsEqual($expectedDataSet, $filterDataSet);
  }

  /**
   * @throws Exception
   */
  public function testUsersAssignWithPath(): void {
    self::$rbac->roles->addPath('/roles_1/roles_2/roles_3');
    self::$rbac->roles->pathId('/roles_1/roles_2/roles_3');

    self::$rbac->users->assign('/roles_1/roles_2', 5);

    $dataSet = $this->getConnection()->createDataSet();

    $filterDataSet = new Filter($dataSet);
    $filterDataSet->addIncludeTables([
      self::$rbac->users->tablePrefix() . 'userroles',
    ]);

    $filterDataSet->setExcludeColumnsForTable(
      self::$rbac->users->tablePrefix() . 'userroles',
      ['assignment_date']
    );

    $expectedDataSet = $this->createFlatXmlDataSet(self::$datasetPath . 'users/expected_assign_with_path.xml');

    $this->assertDataSetsEqual($expectedDataSet, $filterDataSet);
  }

  public function testUsersAssignNoUserID() {
    $this->expectException(RbacUserNotProvidedException::class);

    $result = self::$rbac->users->assign(5);

    $this->assertFalse($result);
  }

  public function testUsersAssignPassNothing() {
    $this->expectException(ArgumentCountError::class);

    self::$rbac->users->assign();
  }

  /*
   * Tests for self::$rbac->Users->hasRole()
   */
  /**
   * @throws Exception
   */
  public function testUsersHasRoleId(): void {
    $roleId = self::$rbac->roles->add('roles_1', 'roles Description 1');

    self::$rbac->users->assign($roleId, 5);

    $result = self::$rbac->users->hasRole($roleId, 5);

    $this->assertTrue($result);
  }

  /**
   * @throws Exception
   */
  public function testUsersHasRoleTitle(): void {
    $roleId = self::$rbac->roles->add('roles_1', 'roles Description 1');

    self::$rbac->users->assign($roleId, 5);

    $result = self::$rbac->users->hasRole('roles_1', 5);

    $this->assertTrue($result);
  }

  /**
   * @throws Exception
   */
  public function testUsersHasRolePath(): void {
    self::$rbac->roles->addPath('/roles_1/roles_2/roles_3');
    $roleId = self::$rbac->roles->pathId('/roles_1/roles_2/roles_3');

    self::$rbac->users->assign($roleId, 5);

    $result = self::$rbac->users->hasRole('/roles_1/roles_2/roles_3', 5);

    $this->assertTrue($result);
  }

  /**
   * @throws Exception
   */
  public function testUsersHasRoleDoesNotHaveRole(): void {
    $roleId = self::$rbac->roles->add('roles_1', 'roles Description 1');

    self::$rbac->users->assign($roleId, 5);

    $result = self::$rbac->users->hasRole(1, 5);

    $this->assertFalse($result);
  }

  /**
   * @throws Exception
   */
  public function testUsersHasRoleNullRole(): void {
    $roleId = self::$rbac->roles->add('roles_1', 'roles Description 1');

    self::$rbac->users->assign($roleId, 5);

    $result = self::$rbac->users->hasRole(null, 5);

    $this->assertFalse($result);
  }

  public function testUsersHasRoleNoUserId(): void {
    $this->expectException(RbacUserNotProvidedException::class);

    self::$rbac->users->hasRole(5);
  }

  public function testUsersHasRolePassNothing() {
    $this->expectException(ArgumentCountError::class);

    self::$rbac->users->hasRole();
  }

  /*
   * Tests for self::$rbac->Users->allRoles()
   */
  /**
   * @throws Exception
   */
  public function testUsersAllRoles() {
    $roleId1 = self::$rbac->roles->add('roles_1', 'roles Description 1');
    $roleId2 = self::$rbac->roles->add('roles_2', 'roles Description 2');
    $roleId3 = self::$rbac->roles->add('roles_3', 'roles Description 3');

    self::$rbac->users->assign($roleId1, 5);
    self::$rbac->users->assign($roleId2, 5);
    self::$rbac->users->assign($roleId3, 5);

    $result = self::$rbac->users->allRoles(5);

    $expected = [
      [
        'id' => 2,
        'lft' => 1,
        'rght' => 2,
        'title' => 'roles_1',
        'description' => 'roles Description 1',
      ],
      [
        'id' => 3,
        'lft' => 3,
        'rght' => 4,
        'title' => 'roles_2',
        'description' => 'roles Description 2',
      ],
      [
        'id' => 4,
        'lft' => 5,
        'rght' => 6,
        'title' => 'roles_3',
        'description' => 'roles Description 3',
      ],
    ];

    $this->assertSame($expected, $result);
  }

  /**
   * @throws Exception
   */
  public function testUsersAllRolesBadRoleNull(): void {
    $result = self::$rbac->users->allRoles(10);

    $this->assertNull($result);
  }

  public function testUsersAllRolesNoRolesEmpty(): void {
    $this->expectException(RbacUserNotProvidedException::class);

    self::$rbac->users->allRoles();
  }

  /*
   * Tests for self::$rbac->Users->roleCount()
   */
  /**
   * @throws Exception
   */
  public function testUsersRoleCount(): void {
    $roleId1 = self::$rbac->roles->add('roles_1', 'roles Description 1');
    $roleId2 = self::$rbac->roles->add('roles_2', 'roles Description 2');
    $roleId3 = self::$rbac->roles->add('roles_3', 'roles Description 3');

    self::$rbac->users->assign($roleId1, 5);
    self::$rbac->users->assign($roleId2, 5);
    self::$rbac->users->assign($roleId3, 5);

    $result = self::$rbac->users->roleCount(5);

    $this->assertSame(3, $result);
  }

  /**
   * @throws Exception
   */
  public function testUsersRoleCountNoRoles(): void {
    $result = self::$rbac->users->roleCount(10);

    $this->assertSame(0, $result);
  }

  public function testUsersRoleCountNoRolesEmpty(): void {
    $this->expectException(RbacUserNotProvidedException::class);

    self::$rbac->users->roleCount();
  }

  /*
   * Tests for self::$rbac->Users->unassign()
   */
  /**
   * @throws Exception
   */
  public function testUsersUnassignId(): void {
    $roleId1 = self::$rbac->roles->add('roles_1', 'roles Description 1');
    $roleId2 = self::$rbac->roles->add('roles_2', 'roles Description 2');
    $roleId3 = self::$rbac->roles->add('roles_3', 'roles Description 3');

    self::$rbac->users->assign($roleId1, 5);
    self::$rbac->users->assign($roleId2, 5);
    self::$rbac->users->assign($roleId3, 5);

    self::$rbac->users->unassign($roleId2, 5);

    $dataSet = $this->getConnection()->createDataSet();

    $filterDataSet = new Filter($dataSet);
    $filterDataSet->addIncludeTables([
      self::$rbac->users->tablePrefix() . 'userroles',
    ]);

    $filterDataSet->setExcludeColumnsForTable(
      self::$rbac->users->tablePrefix() . 'userroles',
      ['assignment_date']
    );

    $expectedDataSet = $this->createFlatXmlDataSet(self::$datasetPath . 'users/expected_unassign.xml');

    $this->assertDataSetsEqual($expectedDataSet, $filterDataSet);
  }

  /**
   * @throws Exception
   */
  public function testUsersUnassignTitle(): void {
    $roleId1 = self::$rbac->roles->add('roles_1', 'roles Description 1');
    $roleId2 = self::$rbac->roles->add('roles_2', 'roles Description 2');
    $roleId3 = self::$rbac->roles->add('roles_3', 'roles Description 3');

    self::$rbac->users->assign($roleId1, 5);
    self::$rbac->users->assign($roleId2, 5);
    self::$rbac->users->assign($roleId3, 5);

    self::$rbac->users->unassign('roles_2', 5);

    $dataSet = $this->getConnection()->createDataSet();

    $filterDataSet = new Filter($dataSet);
    $filterDataSet->addIncludeTables([
      self::$rbac->users->tablePrefix() . 'userroles',
    ]);

    $filterDataSet->setExcludeColumnsForTable(
      self::$rbac->users->tablePrefix() . 'userroles',
      ['assignment_date']
    );

    $expectedDataSet = $this->createFlatXmlDataSet(self::$datasetPath . 'users/expected_unassign.xml');

    $this->assertDataSetsEqual($expectedDataSet, $filterDataSet);
  }

  /**
   * @throws Exception
   */
  public function testUsersUnassignPath(): void {
    $roleId1 = self::$rbac->roles->add('roles_1', 'roles Description 1');
    $roleId2 = self::$rbac->roles->add('roles_2', 'roles Description 2');
    $roleId3 = self::$rbac->roles->add('roles_3', 'roles Description 3');

    self::$rbac->users->assign($roleId1, 5);
    self::$rbac->users->assign($roleId2, 5);
    self::$rbac->users->assign($roleId3, 5);

    self::$rbac->users->unassign('/roles_2', 5);

    $dataSet = $this->getConnection()->createDataSet();

    $filterDataSet = new Filter($dataSet);
    $filterDataSet->addIncludeTables([
      self::$rbac->users->tablePrefix() . 'userroles',
    ]);

    $filterDataSet->setExcludeColumnsForTable(
      self::$rbac->users->tablePrefix() . 'userroles',
      ['assignment_date']
    );

    $expectedDataSet = $this->createFlatXmlDataSet(self::$datasetPath . 'users/expected_unassign.xml');

    $this->assertDataSetsEqual($expectedDataSet, $filterDataSet);
  }

  public function testUsersUnassignNoUserIdException(): void {
    $this->expectException(RbacUserNotProvidedException::class);

    self::$rbac->users->unassign(5);
  }

  public function testUsersUnassignNoRolesException(): void {
    $this->expectException(ArgumentCountError::class);

    self::$rbac->users->unassign();
  }

  /*
   * Tests for self::$rbac->Users->resetAssignments()
   */
  /**
   * @throws Exception
   */
  public function testUsersResetAssignments(): void {
    $roleId1 = self::$rbac->roles->add('roles_1', 'roles Description 1');
    $roleId2 = self::$rbac->roles->add('roles_2', 'roles Description 2');
    $roleId3 = self::$rbac->roles->add('roles_3', 'roles Description 3');

    self::$rbac->users->assign($roleId1, 5);
    self::$rbac->users->assign($roleId2, 5);
    self::$rbac->users->assign($roleId3, 5);

    self::$rbac->users->resetAssignments(true);

    $dataSet = $this->getConnection()->createDataSet();

    $filterDataSet = new Filter($dataSet);
    $filterDataSet->addIncludeTables([
      self::$rbac->users->tablePrefix() . 'userroles',
    ]);

    $filterDataSet->setExcludeColumnsForTable(
      self::$rbac->users->tablePrefix() . 'userroles',
      ['assignment_date']
    );

    $expectedDataSet = $this->createFlatXmlDataSet(self::$datasetPath . 'users/expected_reset_assignments.xml');

    $this->assertDataSetsEqual($expectedDataSet, $filterDataSet);
  }

  public function testUsersResetAssignmentsException(): void {
    $this->expectException(Exception::class);

    self::$rbac->users->resetAssignments();
  }
}
