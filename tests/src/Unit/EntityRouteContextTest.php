<?php

declare(strict_types = 1);

namespace Drupal\Tests\entity_route_context\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Plugin\DataType\EntityAdapter;
use Drupal\Core\Entity\TypedData\EntityDataDefinition;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\TypedData\TypedDataManager;
use Drupal\Core\TypedData\TypedDataManagerInterface;
use Drupal\entity_route_context\ContextProvider\EntityRouteContext;
use Drupal\entity_route_context\EntityRouteContextRouteHelperInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * General tests for entity route context.
 *
 * @group entity_route_context
 */
class EntityRouteContextTest extends UnitTestCase {

  /**
   * Mock typed data manager.
   *
   * @var \Drupal\Core\TypedData\TypedDataManagerInterface
   */
  protected $typedDataManager;

  /**
   * Mock entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Mock route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Mock entity type manager.
   *
   * @var \Drupal\entity_route_context\EntityRouteContextRouteHelperInterface
   */
  protected $helper;

  /**
   * A fake existing entity type ID for testing.
   */
  protected const TEST_ENTITY_TYPE_ID = 'test_entity_type_id';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->typedDataManager = $this->createMock(TypedDataManagerInterface::class);
    $this->typedDataManager = $this->getMockBuilder(TypedDataManager::class)
      ->disableOriginalConstructor()
      ->setMethods([
        'create',
        'createDataDefinition',
        'getDefaultConstraints',
        'getDefinition',
      ])
      ->getMock();
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->routeMatch = $this->createMock(RouteMatchInterface::class);
    $this->helper = $this->createMock(EntityRouteContextRouteHelperInterface::class);
  }

  /**
   * Test no contexts returned if they're not provided by entity route context.
   */
  public function testNoValuesRequested(): void {
    $context = new EntityRouteContext($this->entityTypeManager, $this->routeMatch, $this->helper);
    $this->routeMatch->expects($this->never())->method('getRouteName');
    $this->assertCount(0, $context->getRuntimeContexts(['blah']));
  }

  /**
   * Tests value is returned when a route match represents an entity template.
   */
  public function testValue(): void {
    $entityId = $this->randomMachineName();
    $entity = $this->createMock(EntityInterface::class);
    $entity->expects($this->any())->method('getEntityTypeId')->willReturn(static::TEST_ENTITY_TYPE_ID);
    $entity->expects($this->once())->method('id')->willReturn($entityId);

    $this->typedDataManager->expects($this->once())->method('create')
      ->willReturn(EntityAdapter::createFromEntity($entity));

    $this->typedDataManager->expects($this->once())->method('getDefinition')
      ->willReturn([
        'unwrap_for_canonical_representation' => TRUE,
      ]);

    $entityDefinition = EntityDataDefinition::create();
    $entityDefinition->setEntityTypeId(static::TEST_ENTITY_TYPE_ID);
    $this->typedDataManager->expects($this->once())->method('createDataDefinition')
      ->with('entity:' . static::TEST_ENTITY_TYPE_ID)
      ->willReturn($entityDefinition);

    $this->typedDataManager->expects($this->once())->method('getDefaultConstraints')
      ->willReturn([]);

    $container = new ContainerBuilder();
    $container->set('typed_data_manager', $this->typedDataManager);
    \Drupal::setContainer($container);

    $this->entityTypeManager->expects($this->once())
      ->method('getDefinition')
      ->with(static::TEST_ENTITY_TYPE_ID)
      ->willReturn($this->createMock(EntityTypeInterface::class));

    $this->routeMatch->expects($this->once())->method('getRouteName')
      ->willReturn($this->randomMachineName());

    $parameterBag = new ParameterBag(['some_parameter_name' => $entity]);
    $this->routeMatch->expects($this->once())->method('getParameters')
      ->willReturn($parameterBag);

    $this->helper->expects($this->once())->method('getEntityTypeId')
      ->willReturn(static::TEST_ENTITY_TYPE_ID);

    $context = new EntityRouteContext($this->entityTypeManager, $this->routeMatch, $this->helper);

    $unqualifiedContextId = 'canonical_entity:' . static::TEST_ENTITY_TYPE_ID;
    $contexts = $context->getRuntimeContexts([$unqualifiedContextId]);
    // Only this context may be returned.
    $this->assertCount(1, $contexts);
    $this->assertEquals($entityId, $contexts[$unqualifiedContextId]->getContextValue()->id());
  }

  /**
   * Tests value is returned even if route match doesn't represent an entity.
   */
  public function testValueSupportedUnmatchedRoute(): void {
    $entity = $this->createMock(EntityInterface::class);
    $entity->expects($this->any())->method('getEntityTypeId')->willReturn(static::TEST_ENTITY_TYPE_ID);
    $entity->expects($this->never())->method('id');

    $container = new ContainerBuilder();
    $container->set('typed_data_manager', $this->typedDataManager);
    \Drupal::setContainer($container);

    $this->entityTypeManager->expects($this->once())
      ->method('getDefinition')
      ->with(static::TEST_ENTITY_TYPE_ID)
      ->willReturn($this->createMock(EntityTypeInterface::class));

    $this->routeMatch->expects($this->once())->method('getRouteName')
      ->willReturn($this->randomMachineName());

    $parameterBag = new ParameterBag(['some_parameter_name' => $entity]);
    $this->routeMatch->expects($this->once())->method('getParameters')
      ->willReturn($parameterBag);

    $this->helper->expects($this->once())->method('getEntityTypeId')
      ->willReturn('some_other_entity_type_id');

    $context = new EntityRouteContext($this->entityTypeManager, $this->routeMatch, $this->helper);

    // Should only return what is requested, even if its NULL.
    $unqualifiedContextId = 'canonical_entity:' . static::TEST_ENTITY_TYPE_ID;
    $contexts = $context->getRuntimeContexts([$unqualifiedContextId]);
    // Only this context may be returned.
    $this->assertCount(1, $contexts);
    $this->assertNull($contexts[$unqualifiedContextId]->getContextValue());
  }

  /**
   * Tests if a context is requested for a non existent entity type ID.
   */
  public function testValueProvidedRequestedNonExistent(): void {
    $context = new EntityRouteContext($this->entityTypeManager, $this->routeMatch, $this->helper);

    $this->entityTypeManager->expects($this->once())
      ->method('getDefinition')
      ->with('non_existent_entity_type_id')
      ->willReturn(NULL);

    $this->routeMatch->expects($this->never())->method('getRouteName');

    // Tests a context isn't returned if the entity type doesnt exist.
    $contexts = $context->getRuntimeContexts(['canonical_entity:non_existent_entity_type_id']);
    $this->assertCount(0, $contexts);
  }

}
