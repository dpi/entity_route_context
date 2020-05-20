<?php

declare(strict_types = 1);

namespace Drupal\Tests\entity_route_context\Kernel;

use Drupal\block\Entity\Block;
use Drupal\Core\Access\AccessResultForbidden;
use Drupal\KernelTests\KernelTestBase;

/**
 * General tests for entity route context.
 *
 * @group entity_route_context
 */
class EntityRouteContextTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'entity_route_context',
    'entity_test',
    'block',
    'entity_route_context_block_test',
  ];

  /**
   * Tests block access is allowed and cacheable.
   */
  public function testBlockAccess(): void {
    $block = Block::create([
      'id' => 'my_block',
      'plugin' => 'entity_route_context_block_test_block',
      'status' => TRUE,
    ]);
    $block->setVisibilityConfig('entity_route_context_block_test_condition', [
      'id' => 'entity_route_context_block_test_condition',
      'negate' => FALSE,
      'context_mapping' => [
        'entity_test_1' => '@entity_route_context.entity_route_context:canonical_entity:entity_test',
      ],
    ]);

    /** @var \Drupal\Core\Access\AccessResult $access */
    $access = $block->access('view', NULL, TRUE);
    $this->assertInstanceOf(AccessResultForbidden::class, $access);
    // \Drupal\block\BlockAccessControlHandler::checkAccess will set max age to
    // 0 if the context wasn't supplied.
    $this->assertEquals(-1, $access->getCacheMaxAge());
  }

}
