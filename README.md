# Kirby Language Access

Controls which languages are visible on the frontend and who can edit them in the Panel. Frontend visitors only see the languages you've enabled. Restricted users can only edit content in the languages assigned to them.

## Installation

```bash
composer require medienbaecker/kirby-language-access
```

Or download and place in `site/plugins/kirby-language-access`.

## Setup

### 1. Enable languages

In `site/config/config.php`, specify which languages visitors can see. The default language is always available, but it also doesn't hurt including it in the array:

```php
return [
    'medienbaecker.language-access.languages' => ['de', 'en'],
];
```

### 2. Disable fields for restricted languages

For pages, the plugin registers a custom page model that makes fields read-only automatically. If your project has its own `site/models/default.php`, extend the plugin's model rather than `Page`:

```php
use Medienbaecker\LanguageAccess\LanguageAccessPage;

class DefaultPage extends LanguageAccessPage
{
    // your methods
}
```

Unfortunately there's no equivalent for site and files in Kirby, so your blueprints need to extend the plugin:

```yaml
# site/blueprints/site.yml
extends: language-access/site
```

```yaml
# site/blueprints/files/default.yml
extends: language-access/file
```

### 3. Restrict users

The plugin includes a Translator role blueprint. Create it in your project and tweak the permissions as needed:

```yaml
# site/blueprints/users/translator.yml
extends: language-access/users/translator
```

Create a user with that role in the Panel and pick the languages they're allowed to edit.

## Language menu

`$site->enabledLanguages()` gives you the languages that are publicly available. Here's a simple example of a language menu:

```php
<?php $languages = $kirby->user() ? $kirby->languages() : $site->enabledLanguages() ?>

<nav>
    <?php foreach ($languages as $lang): ?>
        <a href="<?= $page->url($lang->code()) ?>"><?= $lang->name() ?></a>
    <?php endforeach ?>
</nav>
```

## Licence

MIT
