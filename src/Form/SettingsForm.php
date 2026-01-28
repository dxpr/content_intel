<?php

declare(strict_types=1);

namespace Drupal\content_intel\Form;

use Drupal\content_intel\ContentIntelPluginManager;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configuration form for Content Intelligence settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The plugin manager.
   *
   * @var \Drupal\content_intel\ContentIntelPluginManager
   */
  protected ContentIntelPluginManager $pluginManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    $instance->pluginManager = $container->get('plugin.manager.content_intel');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'content_intel_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['content_intel.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('content_intel.settings');
    $enabled_plugins = $config->get('enabled_plugins') ?? [];

    $form['description'] = [
      '#type' => 'markup',
      '#markup' => '<p>' . $this->t('Configure which Content Intelligence plugins are enabled. Disabled plugins will not be used when collecting entity intel data via CLI.') . '</p>',
    ];

    $form['plugins'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Available Plugins'),
      '#tree' => FALSE,
    ];

    $form['plugins']['enabled_plugins'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Enabled Plugins'),
      '#options' => [],
      '#default_value' => $enabled_plugins,
    ];

    foreach ($this->pluginManager->getDefinitions() as $id => $definition) {
      $plugin = $this->pluginManager->createInstance($id);

      $label = $plugin->label();
      $description = $plugin->description();
      $available = $plugin->isAvailable();
      $provider = $definition['provider'] ?? 'unknown';
      $entity_types = $definition['entity_types'] ?? [];

      $option_label = $label;
      if (!$available) {
        $option_label .= ' ' . $this->t('(unavailable - missing dependencies)');
      }

      $form['plugins']['enabled_plugins']['#options'][$id] = $option_label;

      // Add description as a separate element.
      if ($description || !empty($entity_types)) {
        $desc_parts = [];
        if ($description) {
          $desc_parts[] = $description;
        }
        $desc_parts[] = $this->t('Provider: @provider', ['@provider' => $provider]);
        if (!empty($entity_types)) {
          $desc_parts[] = $this->t('Entity types: @types', ['@types' => implode(', ', $entity_types)]);
        }
        else {
          $desc_parts[] = $this->t('Entity types: all');
        }

        $form['plugins']['plugin_' . $id . '_description'] = [
          '#type' => 'markup',
          '#markup' => '<div class="description" style="margin-left: 2em; margin-bottom: 1em; font-size: 0.9em; color: #666;">' . implode('<br>', $desc_parts) . '</div>',
        ];
      }

      // Disable checkbox if plugin is not available.
      if (!$available) {
        $form['plugins']['enabled_plugins'][$id]['#disabled'] = TRUE;
      }
    }

    // If no plugins, show message.
    if (empty($form['plugins']['enabled_plugins']['#options'])) {
      $form['plugins']['no_plugins'] = [
        '#type' => 'markup',
        '#markup' => '<p><em>' . $this->t('No Content Intel plugins are installed.') . '</em></p>',
      ];
      unset($form['plugins']['enabled_plugins']);
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $enabled = array_filter($form_state->getValue('enabled_plugins', []));

    $this->config('content_intel.settings')
      ->set('enabled_plugins', array_values($enabled))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
