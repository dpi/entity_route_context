<?php

declare(strict_types = 1);

namespace Drupal\entity_route_context_block_test\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a condition for testing.
 *
 * @Condition(
 *   id = "entity_route_context_block_test_condition",
 *   label = @Translation("EntityRouteContextBlockTestBlock"),
 *   admin_label = @Translation("EntityRouteContextBlockTestBlock"),
 *   category = @Translation("entity_route_context_block_test"),
 *   context_definitions = {
 *     "entity_test_1" = @ContextDefinition("entity:entity_test", label = @Translation("Entity Test 1"))
 *   }
 * )
 */
class EntityRouteContextBlockTestCondition extends ConditionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function evaluate(): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function summary(): string {
    return 'test';
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['foo'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Foo'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['foo'] = $form_state->getValue('foo');
  }

}
