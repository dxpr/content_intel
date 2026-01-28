<?php

declare(strict_types=1);

namespace Drupal\content_intel\Plugin\ContentIntel;

use Drupal\content_intel\Attribute\ContentIntel;
use Drupal\content_intel\ContentIntelPluginBase;
use Drupal\content_translation\ContentTranslationManagerInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides translation status from the Content Translation module.
 */
#[ContentIntel(
  id: 'content_translation',
  label: new TranslatableMarkup('Translation Status'),
  description: new TranslatableMarkup('Translation coverage and status from Content Translation module.'),
  weight: 20,
)]
class ContentTranslationPlugin extends ContentIntelPluginBase {

  /**
   * The content translation manager.
   *
   * @var \Drupal\content_translation\ContentTranslationManagerInterface|null
   */
  protected ?ContentTranslationManagerInterface $translationManager = NULL;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface|null
   */
  protected ?LanguageManagerInterface $languageManager = NULL;

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    if ($container->has('content_translation.manager')) {
      $instance->translationManager = $container->get('content_translation.manager');
    }

    $instance->languageManager = $container->get('language_manager');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function isAvailable(): bool {
    return $this->translationManager !== NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(ContentEntityInterface $entity): bool {
    if (!$this->translationManager) {
      return FALSE;
    }

    // Check if content translation is enabled for this entity type/bundle.
    return $this->translationManager->isEnabled(
      $entity->getEntityTypeId(),
      $entity->bundle()
    );
  }

  /**
   * {@inheritdoc}
   */
  public function collect(ContentEntityInterface $entity): array {
    if (!$this->translationManager || !$this->languageManager) {
      return [];
    }

    // Get all available languages.
    $all_languages = $this->languageManager->getLanguages();
    $content_languages = [];
    foreach ($all_languages as $langcode => $language) {
      if (!$language->isLocked()) {
        $content_languages[$langcode] = $language->getName();
      }
    }

    // Get entity's translations.
    $translations = $entity->getTranslationLanguages();
    $translated_languages = [];
    $missing_languages = [];

    foreach ($content_languages as $langcode => $name) {
      if (isset($translations[$langcode])) {
        $translated_languages[$langcode] = $name;
      }
      else {
        $missing_languages[$langcode] = $name;
      }
    }

    // Get original language.
    $original_langcode = $entity->language()->getId();

    // Calculate coverage.
    $total_languages = count($content_languages);
    $translated_count = count($translated_languages);
    $coverage_pct = $total_languages > 0
      ? round(($translated_count / $total_languages) * 100, 1)
      : 0;

    // Build translation details.
    $translation_details = [];
    foreach ($translations as $langcode => $language) {
      $translation = $entity->getTranslation($langcode);
      $detail = [
        'langcode' => $langcode,
        'language' => $language->getName(),
        'is_original' => $langcode === $original_langcode,
      ];

      // Try to get translation metadata if available.
      if ($this->translationManager && method_exists($this->translationManager, 'getTranslationMetadata')) {
        try {
          $metadata = $this->translationManager->getTranslationMetadata($translation);
          if ($metadata) {
            $detail['author'] = $metadata->getAuthor()?->getDisplayName();
            $detail['created'] = $metadata->getCreatedTime();
            $detail['changed'] = $metadata->getChangedTime();
            $detail['published'] = $metadata->isPublished();
            $detail['outdated'] = $metadata->isOutdated();
          }
        }
        catch (\Exception $e) {
          // Metadata not available.
        }
      }

      $translation_details[$langcode] = $detail;
    }

    return [
      'translation_enabled' => TRUE,
      'original_language' => [
        'langcode' => $original_langcode,
        'name' => $content_languages[$original_langcode] ?? $original_langcode,
      ],
      'coverage' => [
        'total_languages' => $total_languages,
        'translated_count' => $translated_count,
        'missing_count' => count($missing_languages),
        'coverage_pct' => $coverage_pct,
      ],
      'translated_languages' => $translated_languages,
      'missing_languages' => $missing_languages,
      'translations' => $translation_details,
    ];
  }

}
