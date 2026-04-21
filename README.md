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

## Auto-detect public language

Opt-in: the plugin can take over Kirby's built-in language detection and restrict it to the public language set. Anything Kirby's `languages.detect` does — detecting on `/` and on language-less deep URLs like `/faq` — the plugin does, but only picks among the languages listed in `medienbaecker.language-access.languages`.

```php
return [
    'languages' => [
        'detect' => false, // let the plugin handle this
    ],
    'medienbaecker.language-access.languages' => ['de', 'en'],
    'medienbaecker.language-access.detect'    => true,
    'medienbaecker.language-access.fallback'  => 'en',
];
```

- `detect` (default `false`) — turn on public-only detection.
- `fallback` (default: site default) — language shown when the browser's `Accept-Language` doesn't match any public language. Useful when the site default isn't the best landing for international visitors.

Matching follows Kirby's own three-tier logic (5-char locale, 2-char code, broad locale prefix), then falls back to `fallback`. Responses include `Vary: Accept-Language` so well-behaved caches key on the header — if you're behind an edge cache (nginx, Varnish, CDN), check that the cache honors `Vary: Accept-Language` on the `/` redirect, otherwise visitors may be served another language's cached response.

## Guard

Anonymous visitors trying to reach a non-public language URL (e.g. `/sv/faq` when only `de` and `en` are public) receive a 404. Logged-in users pass through, so translators can still preview their assigned languages.

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
