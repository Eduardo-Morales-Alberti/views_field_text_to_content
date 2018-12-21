<?php

namespace Drupal\views_field_text_to_content\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Render\ViewsRenderPipelineMarkup;
use Drupal\views\ResultRow;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\Core\Entity\Query\QueryFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * A handler to provide a field to show a content link.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("text_to_content")
 */
class TextToContent extends FieldPluginBase {

  /**
   * Query manager.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryManager;

  /**
   * Url generator.
   *
   * @var \Symfony\Component\Routing\Generator\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, QueryFactory $query_manager, UrlGeneratorInterface $url_generator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->queryManager = $query_manager;
    $this->urlGenerator = $url_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.query'),
      $container->get('url_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Do nothing -- to override the parent query.
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    // Override the alter text option to always alter the text.
    $options['alter']['contains']['alter_text'] = ['default' => TRUE];
    $options['hide_alter_empty'] = ['default' => FALSE];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    // Remove the checkbox.
    unset($form['alter']['alter_text']);
    unset($form['alter']['text']['#states']);
    unset($form['alter']['help']['#states']);
    // @TODO Add list to show input
    $form['alter']['text']['#title'] = $this->t('Text to content');
    $form['alter']['text']['#type'] = 'textfield';
   $form['alter']['text']['#description'] =  $this->t('It You may enter data from this view as per the "Replacement patterns" below.');
    $form['alter']['text']['#default_value'] = $this->options['alter']['text'];
    $form['#pre_render'][] = [$this, 'preRenderCustomForm'];
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {

    $alter = $this->options['alter'];
    $tokens = $this->getRenderTokens($alter);
    $text = $this->viewsTokenReplace($alter['text'], $tokens);
    $output = $this->buildOutput($text);

    return ViewsRenderPipelineMarkup::create($output);
  }

  /**
   * {@inheritdoc}
   */
  protected function allowAdvancedRender() {
    return FALSE;
  }

  /**
   * Build output link to content from text.
   */
  public function buildOutput($text) {
    $nid = $this->searchContent($text);
    $output = $text;
    if (!empty($nid)) {

      $url = $this->urlGenerator->generateFromRoute('entity.node.canonical', ['node' => $nid]);
      $to_renderable = [
        '#type' => 'link',
        '#url' => $url,
        '#title' => $text,
        '#attributes' => [
          'alt' => $this->t('Link to @node', ['@node' => $text]),
        ],
      ];
      $output = $this->getRenderer()->render($to_renderable);
    }
    return $output;
  }

  /**
   * Search content using the text parameter.
   *
   * @param string|mixed $text
   *   Text to find the content.
   *
   * @return int|mixed
   *   First Nid finded.
   */
  public function searchContent($text) {
    $query = $this->queryManager->get('node');
    $query->condition('status', 1)
      // @TODO Allow to configure content type from settings form!
      ->condition('type', 'products')
      ->condition('title', $text);
    // @TODO Allow to configure how to find the text (equal, contain, pattern).
    $result = $query->execute();
    // Just return first match.
    $nid = !empty($result) ? reset($result) : NULL;
    return $nid;
  }

  /**
   * Prerender function to move the textarea to the top of a form.
   */
  public function preRenderCustomForm($form) {
    $form['text'] = $form['alter']['text'];
    $form['help'] = $form['alter']['help'];
    unset($form['alter']['text']);
    unset($form['alter']['help']);

    return $form;
  }

}
