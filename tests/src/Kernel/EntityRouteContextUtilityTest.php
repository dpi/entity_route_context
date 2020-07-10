<?php

declare(strict_types = 1);

namespace Drupal\Tests\entity_route_context\Kernel;

use Drupal\Core\Routing\RouteMatch;
use Drupal\entity_route_context\EntityRouteContextRouteHelperInterface;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\Routing\Route;

/**
 * Tests utility service.
 *
 * @coversDefaultClass \Drupal\entity_route_context\EntityRouteContextRouteHelper
 * @group entity_route_context
 */
class EntityRouteContextUtilityTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'entity_route_context',
    'entity_test',
  ];

  /**
   * Tests getting all routes.
   *
   * @covers ::getAllRouteNames
   */
  public function testGetAllRouteNames(): void {
    $result = $this->getHelper()->getAllRouteNames();
    $this->assertEquals('entity_test', $result['entity.entity_test.canonical']);
    $this->assertEquals('entity_test', $result['entity.entity_test.add_form']);
    $this->assertEquals('entity_test', $result['entity.entity_test.edit_form']);
    $this->assertEquals('entity_test', $result['entity.entity_test.delete_form']);
  }

  /**
   * Tests getting routes for an entity type.
   *
   * @covers ::getRouteNames
   */
  public function testGetRouteNames(): void {
    $result = $this->getHelper()->getRouteNames('entity_test');
    $this->assertContains('entity.entity_test.canonical', $result);
    $this->assertContains('entity.entity_test.add_form', $result);
    $this->assertContains('entity.entity_test.edit_form', $result);
    $this->assertContains('entity.entity_test.delete_form', $result);
  }

  /**
   * Tests get link by route match helper.
   *
   * @covers ::getLinkTemplateByRouteMatch
   */
  public function testGetLinkTemplateByRouteMatch(): void {
    $routeName = 'entity.entity_test.edit_form';
    $route = new Route('/entity_test/manage/{entity_test}/edit');
    $routeMatch = new RouteMatch($routeName, $route);
    $result = $this->getHelper()->getLinkTemplateByRouteMatch($routeMatch);
    $this->assertEquals(['entity_test', 'edit-form'], $result);
  }

  /**
   * Get the helper service.
   *
   * @return \Drupal\entity_route_context\EntityRouteContextRouteHelperInterface
   *   The helper service.
   */
  protected function getHelper(): EntityRouteContextRouteHelperInterface {
    return \Drupal::service('entity_route_context.route_helper');
  }

}
