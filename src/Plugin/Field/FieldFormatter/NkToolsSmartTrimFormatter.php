<?php

namespace Drupal\nk_tools\Plugin\Field\FieldFormatter;

# use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\Markup;
use Drupal\filter\Render\FilteredMarkup;
use Drupal\Component\Utility\Html;

use Drupal\smart_trim\Truncate\TruncateHTML;
use Drupal\smart_trim\Plugin\Field\FieldFormatter\SmartTrimFormatter;



/**
 * Plugin implementation of the 'smart_trim' formatter.
 *
 * @FieldFormatter(
 *   id = "nk_tools_smart_trim",
 *   label = @Translation("Nk tools smart trimmed"),
 *   field_types = {
 *     "text",
 *     "text_long",
 *     "text_with_summary",
 *     "string",
 *     "string_long"
 *   },
 *   settings = {
 *     "trim_length" = "300",
 *     "trim_type" = "chars",
 *     "trim_suffix" = "...",
 *     "more_link" = FALSE,
 *     "more_text" = "Read more",
 *     "summary_handler" = "full",
 *     "trim_options" = ""
 *   }
 * )
 */
class NkToolsSmartTrimFormatter extends SmartTrimFormatter {
  
   /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'trim_suffix_link' => 0,
    ] + parent::defaultSettings();
  }

  public function settingsForm(array $form, FormStateInterface $form_state) {
    
    $element = parent::settingsForm($form, $form_state);
    
    $element['trim_length']['#weight'] = -4;
    $element['trim_type']['#weight'] = -3;
    $element['trim_suffix']['#weight'] = -2;

    $element['trim_suffix_link'] = [
      '#title' => $this->t('Suffix as a link?'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('trim_suffix_link'),
      '#description' => $this->t('Make suffix a link to the entity (if one exists)'),
      '#weight' => -1,
    ];

    return $element;

  }


  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode = NULL) { 

    $element = [];
    $setting_trim_options = $this->getSetting('trim_options');
    $settings_summary_handler = $this->getSetting('summary_handler');
    $entity = $items->getEntity();

    foreach ($items as $delta => $item) {
      if ($settings_summary_handler != 'ignore' && !empty($item->summary)) {
        $output = $item->summary;
      }
      else {
        $output = $item->value;
      }

      // Process additional options (currently only HTML on/off).
      if (!empty($setting_trim_options)) {
        // Allow a zero length trim.
        if (!empty($setting_trim_options['trim_zero']) && $this->getSetting('trim_length') == 0) {
          // If the summary is empty, trim to zero length.
          if (empty($item->summary)) {
            $output = '';
          }
          elseif ($settings_summary_handler != 'full') {
            $output = '';
          }
        }

        if (!empty($setting_trim_options['text'])) {
          // Strip caption.
          $output = preg_replace('/<figcaption[^>]*>.*?<\/figcaption>/i', ' ', $output);

          // Strip tags.
          $output = strip_tags($output);

          // Strip out line breaks.
          $output = preg_replace('/\n|\r|\t/m', ' ', $output);

          // Strip out non-breaking spaces.
          $output = str_replace('&nbsp;', ' ', $output);
          $output = str_replace("\xc2\xa0", ' ', $output);

          // Strip out extra spaces.
          $output = trim(preg_replace('/\s\s+/', ' ', $output));
        }
      }

      // Make the trim, provided we're not showing a full summary.
      if ($this->getSetting('summary_handler') != 'full' || empty($item->summary)) {
        $truncate = new TruncateHTML();
        $length = $this->getSetting('trim_length');
        
        $ellipse_text = $this->getSetting('trim_suffix');
        
        if ($this->getSetting('trim_suffix_link')) {
          if ($entity instanceof EntityInterface && $entity->hasLinkTemplate('canonical')) {
            $ellipse_link = $entity->toLink($ellipse_text)->toRenderable();
           
            $link_classes = [
              'class' => ['no-underline', 'hover-underline', 'fs-84']
            ];

            $ellipse_link['#url']->setOption('attributes', $link_classes);

            $link = Link::fromTextAndUrl($ellipse_text, $ellipse_link['#url']);
            $ellipse = '... ' . $link->toString();
          }
          else {
            $ellipse = $ellipse_text;
          }
        }
        else {    
          $ellipse = $ellipse_text;
        }

        if ($this->getSetting('trim_type') == 'words') {
          $output = $truncate->truncateWords($output, $length, $ellipse);
        }
        else {
          $output = $truncate->truncateChars($output, $length, $ellipse);
        }

      }
      $element[$delta] = [
        '#type' => 'processed_text',
        '#text' => FilteredMarkup::create(Html::decodeEntities($output)),
        '#format' => $item->format,
      ];

      // Wrap content in container div.
      if ($this->getSetting('wrap_output')) {
        $element[$delta]['#prefix'] = '<div class="' . $this->getSetting('wrap_class') . '">';
        $element[$delta]['#suffix'] = '</div>';
      }

      // Add the link, if there is one!
      // The entity must have an id already. Content entities usually get their
      // IDs by saving them. In some cases, eg: Inline Entity Form preview there
      // is no ID until everything is saved.
      // https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Entity!Entity.php/function/Entity%3A%3AtoUrl/8.2.x
      if ($this->getSetting('more_link') && $entity->id() && $entity->hasLinkTemplate('canonical')) {
        // But wait! Don't add a more link if the field ends in <!--break-->.
        if (strpos(strrev($output), strrev('<!--break-->')) !== 0) {
          $more = $this->t($this->getSetting('more_text'));
          $class = $this->getSetting('more_class');

          $project_link = $entity->toLink($more)->toRenderable();
          $project_link['#attributes'] = [
            'class' => [
              $class,
            ],
          ];
          $project_link['#prefix'] = '<div class="' . $class . '">';
          $project_link['#suffix'] = '</div>';
          $element[$delta]['more_link'] = $project_link;
        }
      }
    }
  
    return $element;
  }

}