<?php

declare(strict_types = 1);

namespace Drupal\entity_route_context\ContextProvider;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\entity_route_context\EntityRouteContextRouteHelperInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Determines if the route is owned by an entities link template.
 */
class EntityRouteContext implements ContextProviderInterface {

  use StringTranslationTrait;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The route match object.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Entity route helper.
   *
   * @var \Drupal\entity_route_context\EntityRouteContextRouteHelperInterface
   */
  protected $helper;

  /**
   * Map of route matches to entity keyed by route name.
   *
   * @var \Drupal\Core\Entity\EntityInterface[]
   */
  protected $routeMatchedEntity = [];

  /**
   * Name of context variable.
   */
  protected const CANONICAL_ENTITY = 'canonical_entity';

  /**
   * Name prefix of context variable. Entity type ID to be appended to this.
   */
  protected const CANONICAL_ENTITY_PREFIX = 'canonical_entity:';

  /**
   * Constructs a new EntityRouteContext.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match object.
   * @param \Drupal\entity_route_context\EntityRouteContextRouteHelperInterface $helper
   *   Entity route helper.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, RouteMatchInterface $route_match, EntityRouteContextRouteHelperInterface $helper) {
    $this->routeMatch = $route_match;
    $this->helper = $helper;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getRuntimeContexts(array $unqualified_context_ids): array {
    $cacheability = (new CacheableMetadata())->setCacheContexts(['route']);

    $contexts = [];
    foreach ($unqualified_context_ids as $unqualifiedContextId) {
      // Generate cache contexts for only the requested context IDs.
      if (strpos($unqualifiedContextId, static::CANONICAL_ENTITY_PREFIX) === 0) {
        $entityTypeId = substr($unqualifiedContextId, strlen(static::CANONICAL_ENTITY_PREFIX));
        if ($this->entityTypeManager->getDefinition($entityTypeId, FALSE) === NULL) {
          // Ignore if entity type ID missing/no longer exists.
          continue;
        }

        $entity = $this->getRouteMatchEntity($this->routeMatch);
        // Always return a context, even if its value is NULL, so long as the
        // entity type ID is valid.
        $value = ($entity && $entity->getEntityTypeId() === $entityTypeId) ? $entity : NULL;
        $contextDefinition = EntityContextDefinition::create($entityTypeId)->setRequired(FALSE);
        $contexts[$unqualifiedContextId] = (new Context($contextDefinition, $value))
          ->addCacheableDependency(clone $cacheability);
      }
      elseif ($unqualifiedContextId === static::CANONICAL_ENTITY) {
        if ($entity = $this->getRouteMatchEntity($this->routeMatch)) {
          // The multi-purpose generic entity context.
          $contextDefinition = EntityContextDefinition::create($entity->getEntityTypeId())->setRequired(FALSE);
          $contexts[static::CANONICAL_ENTITY] = (new Context($contextDefinition, $entity))
            ->addCacheableDependency(clone $cacheability);
        }
      }
    }

    return $contexts;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableContexts(): array {
    $contexts = [];
    // \Drupal\Core\Plugin\Context\ContextDefinition::dataTypeMatches allows us
    // to provide a generic 'entity', it will match on both 'entity' and more
    // specific types like 'entity:node'.
    $contextDefinition = new ContextDefinition('entity', $this->t('Entity from route'));
    $context = new Context($contextDefinition);
    $contexts[static::CANONICAL_ENTITY] = $context;

    $entityTypeIds = array_keys(array_flip($this->helper->getAllRouteNames()));
    $entityTypes = array_combine($entityTypeIds, array_map(function (string $entityTypeId): string {
      return (string) $this->entityTypeManager->getDefinition($entityTypeId)->getLabel();
    }, $entityTypeIds));

    // Some context select fields show in order that we provide.
    asort($entityTypes);

    foreach ($entityTypes as $entityTypeId => $entityTypeLabel) {
      $context = EntityContext::fromEntityTypeId($entityTypeId, $this->t('@entity_type from route', [
        '@entity_type' => $entityTypeLabel,
      ]));
      $contexts[static::CANONICAL_ENTITY_PREFIX . $entityTypeId] = $context;
    }

    return $contexts;
  }

  /**
   * Determines entity for a route match.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   A route match.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity for the provided route match, or NULL if the route is note an
   *   entity template.
   */
  protected function getRouteMatchEntity(RouteMatchInterface $routeMatch): ?EntityInterface {
    $routeName = $routeMatch->getRouteName();
    if (!$routeName) {
      return NULL;
    }

    if (array_key_exists($routeName, $this->routeMatchedEntity)) {
      return $this->routeMatchedEntity[$routeName];
    }

    $routeEntityTypeId = $this->helper->getEntityTypeId($routeName);

    if (isset($routeEntityTypeId)) {
      // Only handle parameters casted to entity, return first parameter
      // matching type.
      foreach ($this->routeMatch->getParameters() as $parameter) {
        if ($parameter instanceof EntityInterface && ($parameter->getEntityTypeId() === $routeEntityTypeId)) {
          return $this->routeMatchedEntity[$routeName] = $parameter;
        }
      }
    }

    return $this->routeMatchedEntity[$routeName] = NULL;
  }

}
