<?php

/**
 * @file
 * Views file.
 */

/**
 * Implements hook_views_data().
 *
 * Added field value to content plugin to views.
 */
function views_field_to_content_data() {
  $data = [];
  $data['views']['text_to_content'] = [
    'title' => t('Text to content'),
    'help' => t('Search content using text match to title content and link to it.'),
    'field' => [
      'id' => 'text_to_content',
    ],
  ];
  return $data;
}
