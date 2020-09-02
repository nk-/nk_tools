<?php

namespace Drupal\nk_tools_swiper;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of swipers.
 */
class NkToolsSwiperListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label');
    $header['id'] = $this->t('Machine name');
    $header['status'] = $this->t('Status');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\nk_tools_swiperNkToolsSwiperInterface $entity */
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['status'] = $entity->status() ? $this->t('Enabled') : $this->t('Disabled');
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $ops = parent::getDefaultOperations($entity);
    // Do not allow deletion of the default configuration.
    if ($entity->id() == 'default') {
      unset($ops['delete']);
    }
    return $ops;
  }
}