<?php

declare(strict_types=1);

namespace Drupal\content_intel_example\Plugin\ContentIntel;

use Drupal\content_intel\Attribute\ContentIntel;
use Drupal\content_intel\ContentIntelPluginBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides word count statistics for text fields.
 *
 * This is a basic example plugin demonstrating:
 * - Simple collect() implementation without external dependencies.
 * - Working with entity field values.
 * - Returning structured intelligence data.
 */
#[ContentIntel(
  id: 'word_count',
  label: new TranslatableMarkup('Word Count'),
  description: new TranslatableMarkup('Counts words in text fields.'),
  entity_types: [],
  weight: 100,
)]
final class WordCountPlugin extends ContentIntelPluginBase {

  /**
   * Text field types to analyze.
   *
   * @var array
   */
  protected const TEXT_FIELD_TYPES = [
    'text',
    'text_long',
    'text_with_summary',
    'string',
    'string_long',
  ];

  /**
   * {@inheritdoc}
   */
  public function collect(ContentEntityInterface $entity): array {
    $field_counts = [];
    $total_words = 0;
    $total_characters = 0;

    foreach ($entity->getFields() as $field_name => $field) {
      $field_type = $field->getFieldDefinition()->getType();

      // Only process text fields.
      if (!in_array($field_type, self::TEXT_FIELD_TYPES, TRUE)) {
        continue;
      }

      // Skip empty fields.
      if ($field->isEmpty()) {
        continue;
      }

      $field_text = '';
      foreach ($field as $item) {
        // Get the text value, stripping HTML if present.
        $value = $item->value ?? '';
        if (!empty($value)) {
          $field_text .= ' ' . strip_tags((string) $value);
        }
      }

      $field_text = trim($field_text);
      if (empty($field_text)) {
        continue;
      }

      // Count words and characters.
      $words = str_word_count($field_text);
      $characters = mb_strlen($field_text);

      $field_counts[$field_name] = [
        'words' => $words,
        'characters' => $characters,
      ];

      $total_words += $words;
      $total_characters += $characters;
    }

    return [
      'total_words' => $total_words,
      'total_characters' => $total_characters,
      'fields_analyzed' => count($field_counts),
      'field_breakdown' => $field_counts,
    ];
  }

}
