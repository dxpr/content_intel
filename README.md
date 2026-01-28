# Content Intelligence

**The bridge between Drupal content and artificial intelligence.**

Content Intelligence provides structured, machine-readable access to Drupal entity data and aggregated intelligence from multiple sources. Built for AI agents, coding assistants, and automated systems that need to interrogate, analyze, and understand website content.

## Who Is This For?

**Primary audience: AI systems and autonomous agents**

- **AI Coding Assistants** (Claude Code, GitHub Copilot, Cursor) — Query site structure, content types, and field definitions to make informed development decisions
- **Chatbots & Virtual Assistants** — Access content data to provide contextual responses and recommendations
- **Content Analysis Agents** — Retrieve comprehensive entity data for audits, optimization, and quality assessments
- **Automation Workflows** — Process content programmatically based on structured metadata and intelligence

## Features

- **Entity Agnostic**: Works with all content entity types (nodes, taxonomy, media, users, paragraphs, etc.)
- **AI-Optimized Output**: JSON format designed for LLM consumption and tool integration
- **Plugin System**: Extensible architecture using PHP 8 Attributes
- **Drush Commands**: Full CLI access — perfect for AI tool execution via shell
- **Built-in Integrations**: Statistics and Content Translation plugins included
- **Contrib Integrations**: Plugins for Analyze and AI Social Posts modules

## Installation

```bash
composer require drupal/content_intel
drush en content_intel
```

## Configuration

Visit `/admin/config/content/content-intel` to enable/disable plugins.

## Drush Commands

### Discovery Commands

```bash
# List all content entity types
drush ci:types
drush ci:types --format=json

# List bundles for an entity type
drush ci:bundles node
drush ci:bundles taxonomy_term --format=json

# List fields for an entity type/bundle
drush ci:fields node article
drush ci:fields user

# List available intel plugins
drush ci:plugins
drush ci:plugins --format=json
```

### Entity Commands

```bash
# List entities
drush ci:list node
drush ci:list node article --limit=10
drush ci:list taxonomy_term tags --format=json

# Get full intel for a single entity
drush ci:entity node 1
drush ci:entity node 1 --format=json
drush ci:entity node 1 --fields=title,body
drush ci:entity node 1 --plugins=statistics,content_translation

# Get entity summary (basic info only)
drush ci:summary node 1

# Batch collect intel for multiple entities
drush ci:batch node --bundle=article --limit=5 --format=json
drush ci:batch node --ids=1,2,3 --plugins=statistics
```

## Plugin System

### Creating a Plugin

Create a class in `src/Plugin/ContentIntel/` with the attribute:

```php
<?php

namespace Drupal\mymodule\Plugin\ContentIntel;

use Drupal\content_intel\Attribute\ContentIntel;
use Drupal\content_intel\ContentIntelPluginBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Example ContentIntel plugin.
 */
#[ContentIntel(
  id: 'my_plugin',
  label: new TranslatableMarkup('My Plugin'),
  description: new TranslatableMarkup('Description here.'),
  entity_types: ['node'],
  weight: 50,
)]
class MyPlugin extends ContentIntelPluginBase {

  /**
   * {@inheritdoc}
   */
  public function isAvailable(): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(ContentEntityInterface $entity): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function collect(ContentEntityInterface $entity): array {
    return [
      'metric' => 123,
      'status' => 'active',
    ];
  }

}
```

### Built-in Plugins

- **statistics**: Page view counts from Statistics module (nodes only)
- **content_translation**: Translation status and coverage

### Contrib Plugins

- **analyze**: Analysis data from Analyze module plugins
- **ai_social_posts**: Social media posts linked to nodes

## Service API

Other modules can use the collector service directly:

```php
$collector = \Drupal::service('content_intel.collector');

// Get entity types.
$types = $collector->getEntityTypes();

// Get bundles.
$bundles = $collector->getBundles('node');

// Get fields.
$fields = $collector->getFields('node', 'article');

// List entities.
$entities = $collector->listEntities('node', 'article', limit: 10);

// Load and analyze entity.
$entity = $collector->loadEntity('node', 1);
$intel = $collector->collectIntel($entity);

// Get available plugins.
$plugins = $collector->getPlugins();
```

## Requirements

- Drupal 10.3+ or 11.x
- PHP 8.1+

## Optional Integrations

- **Statistics module**: Enables view count tracking
- **Content Translation module**: Enables translation status
- **Analyze module**: Enables content analysis plugins
- **AI Social Posts module**: Enables social post tracking
