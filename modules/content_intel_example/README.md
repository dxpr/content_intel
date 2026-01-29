# Content Intelligence Example

Provides example plugins and hook implementations for the Content Intelligence
module. This module is intended for developer reference only.

## Contents

This module demonstrates:

1. **Basic Plugin** (`WordCountPlugin`): A simple plugin that counts words in
   text fields without requiring external services.

2. **Plugin with Dependency Injection** (`EntityAgePlugin`): A plugin that
   uses injected services to calculate entity age.

3. **Alter Hook Implementation**: Demonstrates the collect alter hook to add
   computed metrics based on collected plugin data.

## Example Plugins

### Word Count Plugin

Location: `src/Plugin/ContentIntel/WordCountPlugin.php`

A basic plugin demonstrating:
- Simple `collect()` implementation
- Working with entity field values
- Returning structured data

### Entity Age Plugin

Location: `src/Plugin/ContentIntel/EntityAgePlugin.php`

An advanced plugin demonstrating:
- Dependency injection via `create()` method
- Using Drupal services (`datetime.time`, `date.formatter`)
- Conditional availability based on entity properties

## Hook Implementation

The module file demonstrates `hook_content_intel_collect_alter()` which:
- Adds a `content_score` metric combining word count data
- Shows how to access and modify collected plugin data

## Usage

Enable the module:

```bash
drush en content_intel_example
```

Test with Drush:

```bash
# List available plugins (should include word_count and entity_age)
drush ci:plugins

# Get intel for a node (will include example plugin data)
drush ci:entity node 1 --format=json
```

## Requirements

- Content Intelligence module
- Drupal 10.3+ or 11.x

## Note

This module is for development reference only. It should not be installed on
production sites. Use it as a template for creating your own plugins.
