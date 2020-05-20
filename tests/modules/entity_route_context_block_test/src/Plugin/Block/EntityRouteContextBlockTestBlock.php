<?php

declare(strict_types = 1);

namespace Drupal\entity_route_context_block_test\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a block for testing.
 *
 * @Block(
 *   id = "entity_route_context_block_test_block",
 *   admin_label = @Translation("EntityRouteContextBlockTestBlock"),
 *   category = @Translation("entity_route_context_block_test")
 * )
 */
class EntityRouteContextBlockTestBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [];
  }

}
