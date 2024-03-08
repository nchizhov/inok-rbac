<?php

namespace Inok\RBAC\Tests;

use Error;
use Exception;
use Inok\RBAC\Lib\PermissionManager;
use Inok\RBAC\Lib\RoleManager;
use PHPUnit\DbUnit\DataSet\Filter;

/**
 * Unit Tests for PhpRbac PSR Wrapper
 **/
class RbacBase extends RbacSetup {

  /**
   * @return PermissionManager | RoleManager | null
  **/
  protected function instance() {
    return null;
  }

  protected function type(): ?string {
    return null;
  }

  /*
   * Tests for $this->instance()->add()
   */
  public function testAddNullTitle(): void {
    $this->expectException(Error::class);

    $this->instance()->add(null, $this->type() . ' Description');
  }

  public function testAddNullDescription(): void {
    $this->expectException(Error::class);

    $this->instance()->add($this->type() . '_title', null);
  }

  public function testAddSequential(): void {
    $this->instance()->add($this->type() . '_title_1', $this->type() . ' Description 1');
    $this->instance()->add($this->type() . '_title_2', $this->type() . ' Description 2');
    $this->instance()->add($this->type() . '_title_3', $this->type() . ' Description 3');

    $queryTable = $this->getConnection()->createQueryTable(
      $this->instance()->tablePrefix() . $this->type(),
      'SELECT id, lft, rght, title, description 
           FROM ' . $this->instance()->tablePrefix() . $this->type()
    );

    $expectedTable = $this->createFlatXmlDataSet(self::$datasetPath . 'base/expected_add_' . $this->type() . '_sequential.xml')
      ->getTable($this->instance()->tablePrefix() . $this->type());

    $this->assertTablesEqual($expectedTable, $queryTable);
  }

  public function testAddHierarchy(): void {
    $type1 = $this->instance()->add('blog', 'Define ' . $this->type() . ' for the Blog');
    $this->instance()->add($this->type() . '_title_1', $this->type() . ' Description 1', $type1);
    $this->instance()->add($this->type() . '_title_2', $this->type() . ' Description 2', $type1);
    $this->instance()->add($this->type() . '_title_3', $this->type() . ' Description 3', $type1);

    $type2 = $this->instance()->add('forum', 'Define ' . $this->type() . ' for the Forums');
    $this->instance()->add($this->type() . '_title_1', $this->type() . ' Description 1', $type2);
    $this->instance()->add($this->type() . '_title_2', $this->type() . ' Description 2', $type2);
    $this->instance()->add($this->type() . '_title_3', $this->type() . ' Description 3', $type2);

    $queryTable = $this->getConnection()->createQueryTable(
      $this->instance()->tablePrefix() . $this->type(),
      'SELECT id, lft, rght, title, description 
           FROM ' . $this->instance()->tablePrefix() . $this->type()
    );

    $expectedTable = $this->createFlatXmlDataSet(self::$datasetPath . 'base/expected_add_' . $this->type() . '_hierarchy.xml')
      ->getTable($this->instance()->tablePrefix() . $this->type());

    $this->assertTablesEqual($expectedTable, $queryTable);
  }

  /*
   * Tests for $this->instance()->count()
   */
  /**
   * @throws Exception
   */
  public function testCount(): void {
    $this->instance()->add($this->type() . '_title_1', $this->type() . ' Description 1');
    $this->instance()->add($this->type() . '_title_2', $this->type() . ' Description 2');
    $this->instance()->add($this->type() . '_title_3', $this->type() . ' Description 3');

    $typeCount = $this->instance()->count();

    $this->assertSame(4, $typeCount);
  }

  /*
   * Tests for $this->instance()->returnId()
   */
  /**
   * @throws Exception
   */
  public function testReturnIdTitle(): void {
    $this->instance()->addPath('/' . $this->type() . '_1/' . $this->type() . '_2');

    $entityId = $this->instance()->returnId($this->type() . '_2');

    $this->assertEquals('3', $entityId);
  }

  /**
   * @throws Exception
   */
  public function testReturnIdPath(): void {
    $this->instance()->addPath('/' . $this->type() . '_1/' . $this->type() . '_2');

    $entityId = $this->instance()->returnId('/' . $this->type() . '_1/' . $this->type() . '_2');

    $this->assertEquals('3', $entityId);
  }

  /**
   * @throws Exception
   */
  public function testReturnIdNullBadParameters(): void {
    $entityId = $this->instance()->returnId($this->type() . '_2');

    $this->assertSame(null, $entityId);
  }

  /**
   * @throws Exception
   */
  public function testReturnIdNullNoParameters(): void {
    $entityId = $this->instance()->returnId();

    $this->assertSame(null, $entityId);
  }

  /**
   * Tests for $this->instance()->titleId()
   */
  public function testGetTitleId(): void {
    $this->instance()->add($this->type() . '_title', $this->type() . ' Description');
    $titleId = $this->instance()->titleId($this->type() . '_title');

    $this->assertSame(2, $titleId);
  }

  public function testGetTitleIdNull(): void {
    $titleId = $this->instance()->titleId($this->type() . '_title');

    $this->assertNull($titleId);
  }

  /*
   * Tests for $this->instance()->getTitle()
   */
  public function testGetTitle(): void {
    $typeId = $this->instance()->add($this->type() . '_title', $this->type() . ' Description');
    $typeTitle = $this->instance()->getTitle($typeId);

    $this->assertSame($this->type() . '_title', $typeTitle);
  }

  public function testGetTitleNull(): void {
    $typeTitle = $this->instance()->getTitle(3);

    $this->assertNull($typeTitle);
  }

  /*
   * Tests for $this->instance()->getDescription()
   */
  public function testGetDescription(): void {
    $typeDescription = $this->instance()->getDescription(1);

    $this->assertSame('root', $typeDescription);
  }

  public function testGetDescriptionNull(): void {
    $typeDescription = $this->instance()->getDescription(2);

    $this->assertNull($typeDescription);
  }

  /*
   * Tests for $this->instance()->edit()
   */
  public function testEditTitle(): void {
    $typeId = $this->instance()->add($this->type() . '_title', $this->type() . ' Description');
    $this->instance()->edit($typeId, $this->type() . '_title_edited');

    $queryTable = $this->getConnection()->createQueryTable(
      $this->instance()->tablePrefix() . $this->type(),
      'SELECT id, lft, rght, title, description 
           FROM ' . $this->instance()->tablePrefix() . $this->type() . ' 
           WHERE id = 2'
    );

    $expectedTable = $this->createFlatXmlDataSet(self::$datasetPath . 'base/expected_edit_' . $this->type() . '_title.xml')
      ->getTable($this->instance()->tablePrefix() . $this->type());

    $this->assertTablesEqual($expectedTable, $queryTable);
  }

  public function testEditDescription(): void {
    $typeId = $this->instance()->add($this->type() . '_title', $this->type() . ' Description');
    $this->instance()->edit($typeId, null, $this->type() . ' Description edited');

    $queryTable = $this->getConnection()->createQueryTable(
      $this->instance()->tablePrefix() . $this->type(),
      'SELECT id, lft, rght, title, description 
           FROM ' . $this->instance()->tablePrefix() . $this->type() . ' 
           WHERE id = 2'
    );

    $expectedTable = $this->createFlatXmlDataSet(self::$datasetPath . 'base/expected_edit_' . $this->type() . '_description.xml')
      ->getTable($this->instance()->tablePrefix() . $this->type());

    $this->assertTablesEqual($expectedTable, $queryTable);
  }

  public function testEditAll(): void {
    $typeId = $this->instance()->add($this->type() . '_title', $this->type() . ' Description');
    $this->instance()->edit($typeId, $this->type() . '_title_edited', $this->type() . ' Description edited');

    $queryTable = $this->getConnection()->createQueryTable(
      $this->instance()->tablePrefix() . $this->type(),
      'SELECT id, lft, rght, title, description 
           FROM ' . $this->instance()->tablePrefix() . $this->type() . ' 
           WHERE id = 2'
    );

    $expectedTable = $this->createFlatXmlDataSet(self::$datasetPath . 'base/expected_edit_' . $this->type() . '_all.xml')
      ->getTable($this->instance()->tablePrefix() . $this->type());

    $this->assertTablesEqual($expectedTable, $queryTable);
  }

  public function testEditNullId(): void {
    $this->instance()->add($this->type() . '_title', $this->type() . ' Description');
    $result = $this->instance()->edit(3, $this->type() . '_title', $this->type() . ' Description');

    $this->assertFalse($result);
  }

  public function testEditNullParameters(): void {
    $typeId = $this->instance()->add($this->type() . '_title', $this->type() . ' Description');
    $result = $this->instance()->edit($typeId);

    $this->assertFalse($result);
  }

  /*
   * Tests for $this->instance()->addPath()
   */
  /**
   * @throws Exception
   */
  public function testAddPathSingle(): void {
    $this->instance()->addPath('/' . $this->type() . '_1/' . $this->type() . '_2/' . $this->type() . '_3');

    $queryTable = $this->getConnection()->createQueryTable(
      $this->instance()->tablePrefix() . $this->type(),
      'SELECT id, lft, rght, title, description 
           FROM ' . $this->instance()->tablePrefix() . $this->type()
    );

    $expectedTable = $this->createFlatXmlDataSet(self::$datasetPath . 'base/expected_add_path_' . $this->type() . '_single.xml')
      ->getTable($this->instance()->tablePrefix() . $this->type());

    $this->assertTablesEqual($expectedTable, $queryTable);
  }

  /**
   * @throws Exception
   */
  public function testAddPathSingleDescription(): void {
    $descriptions = [
      $this->type() . ' Description 1',
      $this->type() . ' Description 2',
      $this->type() . ' Description 3',
    ];

    $this->instance()->addPath('/' . $this->type() . '_1/' . $this->type() . '_2/' . $this->type() . '_3', $descriptions);

    $queryTable = $this->getConnection()->createQueryTable(
      $this->instance()->tablePrefix() . $this->type(),
      'SELECT id, lft, rght, title, description 
           FROM ' . $this->instance()->tablePrefix() . $this->type()
    );

    $expectedTable = $this->createFlatXmlDataSet(self::$datasetPath . 'base/expected_add_path_' . $this->type() . '_single_description.xml')
      ->getTable($this->instance()->tablePrefix() . $this->type());

    $this->assertTablesEqual($expectedTable, $queryTable);
  }

  /**
   * @throws Exception
   */
  public function testAddPathSequential(): void {
    $this->instance()->addPath('/' . $this->type() . '_1/');
    $this->instance()->addPath('/' . $this->type() . '_1/' . $this->type() . '_2/');
    $this->instance()->addPath('/' . $this->type() . '_1/' . $this->type() . '_2/' . $this->type() . '_3');

    $queryTable = $this->getConnection()->createQueryTable(
      $this->instance()->tablePrefix() . $this->type(),
      'SELECT id, lft, rght, title, description
           FROM ' . $this->instance()->tablePrefix() . $this->type()
    );

    $expectedTable = $this->createFlatXmlDataSet(self::$datasetPath . 'base/expected_add_path_' . $this->type() . '_sequential.xml')
      ->getTable($this->instance()->tablePrefix() . $this->type());

    $this->assertTablesEqual($expectedTable, $queryTable);
  }

  /**
   * @throws Exception
   */
  public function testAddPathSequentialDescription(): void {
    $descriptions1 = [
      $this->type() . ' Description 1',
    ];

    $this->instance()->addPath('/' . $this->type() . '_1/', $descriptions1);

    $descriptions2 = [
      null,
      $this->type() . ' Description 2',
    ];

    $this->instance()->addPath('/' . $this->type() . '_1/' . $this->type() . '_2/', $descriptions2);

    $descriptions3 = [
      null,
      null,
      $this->type() . ' Description 3',
    ];

    $this->instance()->addPath('/' . $this->type() . '_1/' . $this->type() . '_2/' . $this->type() . '_3', $descriptions3);

    $queryTable = $this->getConnection()->createQueryTable(
      $this->instance()->tablePrefix() . $this->type(),
      'SELECT id, lft, rght, title, description 
           FROM ' . $this->instance()->tablePrefix() . $this->type()
    );

    $expectedTable = $this->createFlatXmlDataSet(self::$datasetPath . 'base/expected_add_path_' . $this->type() . '_sequential_description.xml')
      ->getTable($this->instance()->tablePrefix() . $this->type());

    $this->assertTablesEqual($expectedTable, $queryTable);
  }

  /**
   * @throws Exception
   */
  public function testAddPathHierarchy(): void {

    $this->instance()->addPath('/' . $this->type() . '_1/' . $this->type() . '_2/' . $this->type() . '_3');
    $this->instance()->addPath('/' . $this->type() . '_1/' . $this->type() . '_2/' . $this->type() . '_4');

    $this->instance()->addPath('/' . $this->type() . '_12/' . $this->type() . '_13/' . $this->type() . '_14');
    $this->instance()->addPath('/' . $this->type() . '_12/' . $this->type() . '_15/' . $this->type() . '_11');

    $this->instance()->addPath('/' . $this->type() . '_23/' . $this->type() . '_24/' . $this->type() . '_25');
    $this->instance()->addPath('/' . $this->type() . '_33/' . $this->type() . '_34/' . $this->type() . '_35');

    $queryTable = $this->getConnection()->createQueryTable(
      $this->instance()->tablePrefix() . $this->type(),
      'SELECT id, lft, rght, title, description
           FROM ' . $this->instance()->tablePrefix() . $this->type()
    );

    $expectedTable = $this->createFlatXmlDataSet(self::$datasetPath . 'base/expected_add_path_' . $this->type() . '_hierarchy.xml')
      ->getTable($this->instance()->tablePrefix() . $this->type());

    $this->assertTablesEqual($expectedTable, $queryTable);
  }

  /**
   * @throws Exception
   */
  public function testAddPathHierarchyDescription(): void {
    $descriptions1 = [
      $this->type() . ' Description 1',
      $this->type() . ' Description 2',
      $this->type() . ' Description 3',
    ];

    $this->instance()->addPath('/' . $this->type() . '_1/' . $this->type() . '_2/' . $this->type() . '_3', $descriptions1);

    $descriptions2 = [
      null,
      null,
      $this->type() . ' Description 4',
    ];

    $this->instance()->addPath('/' . $this->type() . '_1/' . $this->type() . '_2/' . $this->type() . '_4', $descriptions2);

    $descriptions3 = [
      $this->type() . ' Description 12',
      $this->type() . ' Description 13',
      $this->type() . ' Description 14',
    ];

    $this->instance()->addPath('/' . $this->type() . '_12/' . $this->type() . '_13/' . $this->type() . '_14', $descriptions3);

    $descriptions4 = [
      null,
      $this->type() . ' Description 15',
      $this->type() . ' Description 11',
    ];

    $this->instance()->addPath('/' . $this->type() . '_12/' . $this->type() . '_15/' . $this->type() . '_11', $descriptions4);

    $descriptions5 = [
      $this->type() . ' Description 23',
      $this->type() . ' Description 24',
      $this->type() . ' Description 25',
    ];

    $this->instance()->addPath('/' . $this->type() . '_23/' . $this->type() . '_24/' . $this->type() . '_25', $descriptions5);

    $descriptions6 = [
      $this->type() . ' Description 33',
      $this->type() . ' Description 34',
      $this->type() . ' Description 35',
    ];

    $this->instance()->addPath('/' . $this->type() . '_33/' . $this->type() . '_34/' . $this->type() . '_35', $descriptions6);

    $queryTable = $this->getConnection()->createQueryTable(
      $this->instance()->tablePrefix() . $this->type(),
      'SELECT id, lft, rght, title, description 
           FROM ' . $this->instance()->tablePrefix() . $this->type()
    );

    $expectedTable = $this->createFlatXmlDataSet(self::$datasetPath . 'base/expected_add_path_' . $this->type() . '_hierarchy_description.xml')
      ->getTable($this->instance()->tablePrefix() . $this->type());

    $this->assertTablesEqual($expectedTable, $queryTable);
  }

  /**
   * @throws Exception
   */
  public function testAddPathReturnNodesCreatedCountTwoCreated(): void {
    $this->instance()->addPath('/' . $this->type() . '_1/');
    $nodesCreated = $this->instance()->addPath('/' . $this->type() . '_1/' . $this->type() . '_2/' . $this->type() . '_3');

    $this->assertSame(2, $nodesCreated);
  }

  /**
   * @throws Exception
   */
  public function testAddPathReturnNodesCreatedCountNoneCreated(): void {
    $this->instance()->addPath('/' . $this->type() . '_1/' . $this->type() . '_2/' . $this->type() . '_3');
    $nodesCreated = $this->instance()->addPath('/' . $this->type() . '_1/' . $this->type() . '_2/' . $this->type() . '_3');

    $this->assertSame(0, $nodesCreated);
  }

  public function testAddPathBadPath(): void {
    $this->expectException(Exception::class);

    $this->instance()->addPath('permissions_1/permissions_2//permissions_3');
  }

  /*
   * Tests for $this->instance()->pathId()
   */
  /**
   * @throws Exception
   */
  public function testPathID(): void {
    $this->instance()->addPath('/' . $this->type() . '_1/');
    $this->instance()->addPath('/' . $this->type() . '_1/' . $this->type() . '_2/');
    $this->instance()->addPath('/' . $this->type() . '_1/' . $this->type() . '_2/' . $this->type() . '_3');

    $pathId = $this->instance()->pathId('/' . $this->type() . '_1/' . $this->type() . '_2');

    $this->assertSame('3', $pathId);
  }

  /**
   * @throws Exception
   */
  public function testPathIDNullBadPath(): void {
    $this->instance()->addPath('/' . $this->type() . '_1/');
    $this->instance()->addPath('/' . $this->type() . '_1/' . $this->type() . '_2/');
    $this->instance()->addPath('/' . $this->type() . '_1/' . $this->type() . '_2/' . $this->type() . '_3');

    $pathId = $this->instance()->pathId($this->type() . '_2');

    $this->assertNull($pathId);
  }

  /**
   * @throws Exception
   */
  public function testPathIDGroupConcatMaxCharCountShortCount(): void {
    $this->instance()->addPath('/first_depth0/first_depth1/first_depth2/first_depth3/first_depth4/first_depth5/first_depth6/first_depth7/first_depth8/first_depth9/first_depth10/final_109/first_depth11');
    $this->instance()->addPath('/second_depth0/second_depth1/second_depth2/second_depth3/second_depth4/second_depth5/second_depth6/second_depth7/second_depth8/second_depth9/second_depth10/second_depth11/second_depth12/second_depth13/second_depth14/second_depth15/second_depth16/second_depth17/second_depth18/second_depth19/second_depth20/second_depth21/second_depth22/second_depth23/second_depth24/second_depth25/second_depth26/second_depth27/second_depth28/second_depth29/second_depth30/second_depth31/second_depth32/second_depth33/second_depth34/second_depth35/second_depth36/second_depth37/second_depth38/second_depth39/second_depth40/second_depth41/second_depth42/second_depth43/second_depth44/second_depth45/second_depth46/second_depth47/second_depth48/second_depth49/second_depth50/second_depth51/second_depth52/second_depth53/second_depth54/second_depth55/second_depth56/second_depth57/second_depth58/second_depth59/second_depth60/second_depth61/second_depth62/second_depth63/second_depth64/second_depth65/second_depth66/second_depth67/second_depth68/second_depth69/second_depth70/second_depth71/second_depth72/second_depth73/second_depth74/second_depth75/second_depth76/second_depth77/second_depth78/second_depth79/second_depth80/second_depth81/second_depth82/second_depth83/second_depth84/second_depth85/second_depth86/second_depth87/second_depth88/second_depth89/second_depth90/second_depth91/second_depth92/second_depth93/second_depth94/second_depth95/second_depth96/second_depth97/second_depth98/second_depth99/second_depth100/second_depth101/second_depth102/second_depth103/second_depth104/second_depth105/second_depth106/second_depth107/second_depth108/second_depth109/final_109');

    $pathId = $this->instance()->pathId("/first_depth0/first_depth1/first_depth2/first_depth3/first_depth4/first_depth5/first_depth6/first_depth7/first_depth8/first_depth9/first_depth10/final_109");

    $this->assertSame('13', $pathId);
  }

  /**
   * @throws Exception
   */
  public function testPathIDGroupConcatMaxCharCountLongCount(): void {
    $this->instance()->addPath('/first_depth0/first_depth1/first_depth2/first_depth3/first_depth4/first_depth5/first_depth6/first_depth7/first_depth8/first_depth9/first_depth10/first_depth11');
    $this->instance()->addPath('/second_depth0/second_depth1/second_depth2/second_depth3/second_depth4/second_depth5/second_depth6/second_depth7/second_depth8/second_depth9/second_depth10/second_depth11/second_depth12/second_depth13/second_depth14/second_depth15/second_depth16/second_depth17/second_depth18/second_depth19/second_depth20/second_depth21/second_depth22/second_depth23/second_depth24/second_depth25/second_depth26/second_depth27/second_depth28/second_depth29/second_depth30/second_depth31/second_depth32/second_depth33/second_depth34/second_depth35/second_depth36/second_depth37/second_depth38/second_depth39/second_depth40/second_depth41/second_depth42/second_depth43/second_depth44/second_depth45/second_depth46/second_depth47/second_depth48/second_depth49/second_depth50/second_depth51/second_depth52/second_depth53/second_depth54/second_depth55/second_depth56/second_depth57/second_depth58/second_depth59/second_depth60/second_depth61/second_depth62/second_depth63/second_depth64/second_depth65/second_depth66/second_depth67/second_depth68/second_depth69/second_depth70/second_depth71/second_depth72/second_depth73/second_depth74/second_depth75/second_depth76/second_depth77/second_depth78/second_depth79/second_depth80/second_depth81/second_depth82/second_depth83/second_depth84/second_depth85/second_depth86/second_depth87/second_depth88/second_depth89/second_depth90/second_depth91/second_depth92/second_depth93/second_depth94/second_depth95/second_depth96/second_depth97/second_depth98/second_depth99/second_depth100/second_depth101/second_depth102/second_depth103/second_depth104/second_depth105/second_depth106/second_depth107/second_depth108/second_depth109/final_109');

    $pathId = $this->instance()->pathId("/second_depth0/second_depth1/second_depth2/second_depth3/second_depth4/second_depth5/second_depth6/second_depth7/second_depth8/second_depth9/second_depth10/second_depth11/second_depth12/second_depth13/second_depth14/second_depth15/second_depth16/second_depth17/second_depth18/second_depth19/second_depth20/second_depth21/second_depth22/second_depth23/second_depth24/second_depth25/second_depth26/second_depth27/second_depth28/second_depth29/second_depth30/second_depth31/second_depth32/second_depth33/second_depth34/second_depth35/second_depth36/second_depth37/second_depth38/second_depth39/second_depth40/second_depth41/second_depth42/second_depth43/second_depth44/second_depth45/second_depth46/second_depth47/second_depth48/second_depth49/second_depth50/second_depth51/second_depth52/second_depth53/second_depth54/second_depth55/second_depth56/second_depth57/second_depth58/second_depth59/second_depth60/second_depth61/second_depth62/second_depth63/second_depth64/second_depth65/second_depth66/second_depth67/second_depth68/second_depth69/second_depth70/second_depth71/second_depth72/second_depth73/second_depth74/second_depth75/second_depth76/second_depth77/second_depth78/second_depth79/second_depth80/second_depth81/second_depth82/second_depth83/second_depth84/second_depth85/second_depth86/second_depth87/second_depth88/second_depth89/second_depth90/second_depth91/second_depth92/second_depth93/second_depth94/second_depth95/second_depth96/second_depth97/second_depth98/second_depth99/second_depth100/second_depth101/second_depth102/second_depth103/second_depth104/second_depth105/second_depth106/second_depth107/second_depth108/second_depth109/final_109");

    $this->assertSame('124', $pathId);
  }

  /*
   * Tests for $this->instance()->getPath()
   */
  /**
   * @throws Exception
   */
  public function testPath(): void {
    $this->instance()->addPath('/' . $this->type() . '_1/' . $this->type() . '_2/' . $this->type() . '_3');

    $pathReturned = $this->instance()->getPath(3);

    $this->assertSame('/' . $this->type() . '_1/' . $this->type() . '_2', $pathReturned);
  }

  /**
   * @throws Exception
   */
  public function testGetPathNull(): void {
    $this->instance()->addPath('/' . $this->type() . '_1/' . $this->type() . '_2/' . $this->type() . '_3');

    $pathReturned = $this->instance()->getPath(5);

    $this->assertNull($pathReturned);
  }

  /*
   * Tests for $this->instance()->children()
   */
  /**
   * @throws Exception
   */
  public function testChildren(): void {
    $this->instance()->addPath('/' . $this->type() . '_1/' . $this->type() . '_2/' . $this->type() . '_3/' . $this->type() . '_4/' . $this->type() . '_5');
    $this->instance()->addPath('/' . $this->type() . '_1/' . $this->type() . '_2/' . $this->type() . '_3/' . $this->type() . '_6/' . $this->type() . '_7');
    $pathId = $this->instance()->pathId('/' . $this->type() . '_1/' . $this->type() . '_2/' . $this->type() . '_3');

    $children = $this->instance()->children($pathId);

    $expected = [
      [
        'id' => 5,
        'lft' => 4,
        'rght' => 7,
        'title' => $this->type() . '_4',
        'description' => '',
      ],
      [
        'id' => 7,
        'lft' => 8,
        'rght' => 11,
        'title' => $this->type() . '_6',
        'description' => '',
      ]
    ];

    $this->assertSame($expected, $children);
  }

  public function testChildrenNullBadID(): void {
    $children = $this->instance()->children(20);

    $this->assertNull($children);
  }

  /*
   * Tests for $this->instance()->descendants()
   */
  /**
   * @throws Exception
   */
  public function testDescendants(): void {
    $this->instance()->addPath('/' . $this->type() . '_1/' . $this->type() . '_2/' . $this->type() . '_3/' . $this->type() . '_4/' . $this->type() . '_5');
    $this->instance()->addPath('/' . $this->type() . '_1/' . $this->type() . '_2/' . $this->type() . '_3/' . $this->type() . '_6/' . $this->type() . '_7');
    $pathId = $this->instance()->pathId('/' . $this->type() . '_1/' . $this->type() . '_2/' . $this->type() . '_3');

    $descendants = $this->instance()->descendants($pathId);

    $expected = [
      $this->type() . '_4' => [
        'id' => 5,
        'lft' => 4,
        'rght' => 7,
        'title' => $this->type() . '_4',
        'description' => '',
        'depth' => 1,
      ],
      $this->type() . '_5' => [
        'id' => 6,
        'lft' => 5,
        'rght' => 6,
        'title' => $this->type() . '_5',
        'description' => '',
        'depth' => 2,
      ],
      $this->type() . '_6' => [
        'id' => 7,
        'lft' => 8,
        'rght' => 11,
        'title' => $this->type() . '_6',
        'description' => '',
        'depth' => 1,
      ],
      $this->type() . '_7' => [
        'id' => 8,
        'lft' => 9,
        'rght' => 10,
        'title' => $this->type() . '_7',
        'description' => '',
        'depth' => 2,
      ],
    ];

    $this->assertSame($expected, $descendants);
  }

  public function testDescendantsEmptyBadID(): void {
    $descendants = $this->instance()->descendants(20);

    $this->assertEmpty($descendants);
  }

  /*
   * Tests for $this->instance()->depth()
   */
  /**
   * @throws Exception
   */
  public function testDepth(): void {
    $this->instance()->addPath('/' . $this->type() . '_1/' . $this->type() . '_2/' . $this->type() . '_3/' . $this->type() . '_4/' . $this->type() . '_5');
    $pathId = $this->instance()->pathId('/' . $this->type() . '_1/' . $this->type() . '_2/' . $this->type() . '_3');

    $depth = $this->instance()->depth($pathId);

    $this->assertSame(3, $depth);
  }

  public function testDepthBadID(): void {
    $depth = $this->instance()->depth(20);

    $this->assertSame(-1, $depth);
  }

  /*
   * Tests for $this->instance()->parentNode()
   */
  /**
   * @throws Exception
   */
  public function testParentNode(): void {
    $this->instance()->addPath('/' . $this->type() . '_1/' . $this->type() . '_2/' . $this->type() . '_3/' . $this->type() . '_4/' . $this->type() . '_5');
    $this->instance()->addPath('/' . $this->type() . '_1/' . $this->type() . '_2/' . $this->type() . '_3/' . $this->type() . '_6/' . $this->type() . '_7');
    $pathId = $this->instance()->pathId('/' . $this->type() . '_1/' . $this->type() . '_2/' . $this->type() . '_3');

    $parentNode = $this->instance()->parentNode($pathId);

    $expected = [
      'id' => 3,
      'lft' => 2,
      'rght' => 13,
      'title' => $this->type() . '_2',
      'description' => '',
    ];

    $this->assertSame($expected, $parentNode);
  }

  public function testParentNodeNullBadID(): void {
    $parentNode = $this->instance()->parentNode(20);

    $this->assertNull($parentNode);
  }

  /*
   * Tests for $this->instance()->assign()
   */
  /**
   * @throws Exception
   */
  public function testAssignWithId(): void {
    $permId = self::$rbac->permissions->add('permissions_1', 'permissions Description 1');
    $roleId = self::$rbac->roles->add('roles_1', 'roles Description 1');

    $this->instance()->assign($roleId, $permId);

    $dataSet = $this->getConnection()->createDataSet();

    $filterDataSet = new Filter($dataSet);
    $filterDataSet->addExcludeTables([$this->instance()->tablePrefix() . 'userroles']);

    $filterDataSet->setExcludeColumnsForTable(
      $this->instance()->tablePrefix() . 'rolepermissions',
      ['assignment_date']
    );

    $expectedDataSet = $this->createFlatXmlDataSet(self::$datasetPath . 'base/expected_assign_' . $this->type() . '.xml');

    $this->assertDataSetsEqual($expectedDataSet, $filterDataSet);
  }

  /**
   * @throws Exception
   */
  public function testAssignWithTitle(): void {
    self::$rbac->permissions->add('permissions_1', 'permissions Description 1');
    self::$rbac->roles->add('roles_1', 'roles Description 1');

    $this->instance()->assign('roles_1', 'permissions_1');

    $dataSet = $this->getConnection()->createDataSet();

    $filterDataSet = new Filter($dataSet);
    $filterDataSet->addExcludeTables([$this->instance()->tablePrefix() . 'userroles']);

    $filterDataSet->setExcludeColumnsForTable(
      $this->instance()->tablePrefix() . 'rolepermissions',
      ['assignment_date']
    );

    $expectedDataSet = $this->createFlatXmlDataSet(self::$datasetPath . 'base/expected_assign_' . $this->type() . '.xml');

    $this->assertDataSetsEqual($expectedDataSet, $filterDataSet);
  }

  /**
   * @throws Exception
   */
  public function testAssignWithPath(): void {
    self::$rbac->permissions->add('permissions_1', 'permissions Description 1');
    self::$rbac->roles->add('roles_1', 'roles Description 1');

    $this->instance()->assign('/roles_1', '/permissions_1');

    $dataSet = $this->getConnection()->createDataSet();

    $filterDataSet = new Filter($dataSet);
    $filterDataSet->addExcludeTables([$this->instance()->tablePrefix() . 'userroles']);

    $filterDataSet->setExcludeColumnsForTable(
      $this->instance()->tablePrefix() . 'rolepermissions',
      ['assignment_date']
    );

    $expectedDataSet = $this->createFlatXmlDataSet(self::$datasetPath . 'base/expected_assign_' . $this->type() . '.xml');

    $this->assertDataSetsEqual($expectedDataSet, $filterDataSet);
  }

  /*
   * Tests for $this->instance()->unassign()
   */
  /**
   * @throws Exception
   */
  public function testUnassignId(): void {
    $permId = self::$rbac->permissions->add('permissions_1', 'permissions Description 1');
    $roleId = self::$rbac->roles->add('roles_1', 'roles Description 1');

    $this->instance()->assign($roleId, $permId);
    $this->instance()->unassign($roleId, $permId);

    $dataSet = $this->getConnection()->createDataSet();

    $filterDataSet = new Filter($dataSet);
    $filterDataSet->addIncludeTables([
      self::$rbac->users->tablePrefix() . 'rolepermissions',
    ]);

    $filterDataSet->setExcludeColumnsForTable(
      $this->instance()->tablePrefix() . 'rolepermissions',
      ['assignment_date']
    );

    $expectedDataSet = $this->createFlatXmlDataSet(self::$datasetPath . 'base/expected_unassign_' . $this->type() . '.xml');

    $this->assertDataSetsEqual($expectedDataSet, $filterDataSet);
  }

  /**
   * @throws Exception
   */
  public function testUnassignTitle(): void {
    $permId = self::$rbac->permissions->add('permissions_1', 'permissions Description 1');
    $roleId = self::$rbac->roles->add('roles_1', 'roles Description 1');

    $this->instance()->assign($roleId, $permId);
    $this->instance()->unassign('roles_1', 'permissions_1');

    $dataSet = $this->getConnection()->createDataSet();

    $filterDataSet = new Filter($dataSet);
    $filterDataSet->addIncludeTables([
      self::$rbac->users->tablePrefix() . 'rolepermissions',
    ]);

    $filterDataSet->setExcludeColumnsForTable(
      $this->instance()->tablePrefix() . 'rolepermissions',
      ['assignment_date']
    );

    $expectedDataSet = $this->createFlatXmlDataSet(self::$datasetPath . 'base/expected_unassign_' . $this->type() . '.xml');

    $this->assertDataSetsEqual($expectedDataSet, $filterDataSet);
  }

  /**
   * @throws Exception
   */
  public function testUnassignPath(): void {
    $permId = self::$rbac->permissions->add('permissions_1', 'permissions Description 1');
    $roleId = self::$rbac->roles->add('roles_1', 'roles Description 1');

    $this->instance()->assign($roleId, $permId);
    $this->instance()->unassign('/roles_1', '/permissions_1');

    $dataSet = $this->getConnection()->createDataSet();

    $filterDataSet = new Filter($dataSet);
    $filterDataSet->addIncludeTables([
      self::$rbac->users->tablePrefix() . 'rolepermissions',
    ]);

    $filterDataSet->setExcludeColumnsForTable(
      $this->instance()->tablePrefix() . 'rolepermissions',
      ['assignment_date']
    );

    $expectedDataSet = $this->createFlatXmlDataSet(self::$datasetPath . 'base/expected_unassign_' . $this->type() . '.xml');

    $this->assertDataSetsEqual($expectedDataSet, $filterDataSet);
  }

  /*
   * Tests for $this->instance()->resetAssignments()
   */
  /**
   * @throws Exception
   */
  public function testResetPermRoleAssignments(): void {
    $permId1 = self::$rbac->permissions->add('permissions_1', 'permissions Description 1');
    $permId2 = self::$rbac->permissions->add('permissions_2', 'permissions Description 2');
    $permId3 = self::$rbac->permissions->add('permissions_3', 'permissions Description 3');

    $roleId1 = self::$rbac->roles->add('roles_1', 'roles Description 1');
    $roleId2 = self::$rbac->roles->add('roles_2', 'roles Description 2');
    $roleId3 = self::$rbac->roles->add('roles_3', 'roles Description 3');

    $this->instance()->assign($roleId1, $permId1);
    $this->instance()->assign($roleId2, $permId2);
    $this->instance()->assign($roleId3, $permId3);

    $this->instance()->resetAssignments(true);

    $dataSet = $this->getConnection()->createDataSet();

    $filterDataSet = new Filter($dataSet);
    $filterDataSet->addIncludeTables([
      self::$rbac->users->tablePrefix() . 'rolepermissions',
    ]);

    $filterDataSet->setExcludeColumnsForTable(
      $this->instance()->tablePrefix() . 'rolepermissions',
      ['assignment_date']
    );

    $expectedDataSet = $this->createFlatXmlDataSet(self::$datasetPath . 'base/expected_reset_assignments_' . $this->type() . '.xml');

    $this->assertDataSetsEqual($expectedDataSet, $filterDataSet);
  }

  public function testResetPermRoleAssignmentsException(): void {
    $this->expectException(Exception::class);

    $this->instance()->resetAssignments();
  }

  /*
   * Tests for $this->instance()->reset()
   */
  /**
   * @throws Exception
   */
  public function testReset() {
    $this->instance()->add($this->type() . '_title_1', $this->type() . ' Description 1');
    $this->instance()->add($this->type() . '_title_2', $this->type() . ' Description 2');
    $this->instance()->add($this->type() . '_title_3', $this->type() . ' Description 3');

    $this->instance()->reset(true);

    $dataSet = $this->getConnection()->createDataSet();

    $filterDataSet = new Filter($dataSet);
    $filterDataSet->addIncludeTables([
      $this->instance()->tablePrefix() . $this->type(),
    ]);

    $expectedDataSet = $this->createFlatXmlDataSet(self::$datasetPath . 'base/expected_reset_' . $this->type() . '.xml');

    $this->assertDataSetsEqual($expectedDataSet, $filterDataSet);
  }

  public function testResetException(): void {
    $this->expectException(Exception::class);

    $this->instance()->reset();
  }
}
