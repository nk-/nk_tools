<?php

namespace Drupal\nk_tools\TwigExtension;

use Drupal\Core\Template\TwigExtension;

use Drupal\nk_tools\DiploFormattersService;

/**
 * extend Drupal's Twig_Extension class
 */
class NkToolsTwigExtension extends \Twig_Extension {

  /**
   * {@inheritdoc}
   * Let Drupal know the name of your extension
   * must be unique name, string
   */
  public function getName() {
    return 'nk_tools.twigextension';
  }


  /**
   * {@inheritdoc}
   * Return your custom twig function to Drupal
   */
  public function getFunctions() {
    return [
      new \Twig_SimpleFunction('diplo_embed_view', [$this, 'diplo_embed_view'], ['is_safe' => ['html']]),
    ];
  }

  /**
   * {@inheritdoc}
   * Return your custom twig filter to Drupal
   */
  public function getFilters() {
    return [
      new \Twig_SimpleFilter('replace_tokens', [$this, 'replace_tokens']),
    ];
  }


  /**
   * Returns $_GET query parameter
   *
   * @param string $name
   *   name of the query parameter
   *
   * @return string
   *   value of the query parameter name
   */
  public function diplo_embed_view($viewId, $displayId, array $arguments = [], $render = NULL, $json = NULL) {
    $view = \Drupal::service('nk_tools.main_service')->getView($viewId, $displayId, $arguments, TRUE);
    return $view;
  }

  /**
   * Replaces available values to entered tokens
   * Also accept HTML text
   *
   * @param string $text
   *   replaceable tokens with/without entered HTML text
   *
   * @return string
   *   replaced token values with/without entered HTML text
   */
  public function replace_tokens($text) {
    return \Drupal::token()->replace($text);
  }

}