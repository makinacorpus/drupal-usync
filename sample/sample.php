<?php
return array(
  'field' => array(
    's_image_small' => array(
      'type' => 'image',
      'label' => "The small image"
    ),
    's_image_big' => array(
      'type' => 'image',
      'label' => "The big image"
    ),
    's_text' => array(
      'type' => 'text_with_summary',
      'label' => "The text",
    ),
  ),
  'entity' => array(
    'node' => array(
      's_a' => array(
        'label' => "Sample A",
        'comment' => false,
        'field' => array(
          's_image_small',
          's_image_big',
          's_text',
        ),
        'view' => array(
          'teaser' => array(
            's_image_small' => array(
              'type' => 'image',
              'image_style' => 'thumbnail',
            ),
            's_text' => 'text_summary',
          ),
          'intermediate' => 'teaser',
          'full' => array(
            's_image_big' => true,
            's_text' => true,
          ),
        ),
      ),
    ),
  ),
);