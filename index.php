<?php

use Kirby\Cms\App as Kirby;
use Kirby\Filesystem\F;
use Medienbaecker\LanguageAccess\LanguageAccessDetect;
use Medienbaecker\LanguageAccess\LanguageAccessGuard;

require __DIR__ . '/src/LanguageAccessGuard.php';
require __DIR__ . '/src/LanguageAccessDetect.php';
require __DIR__ . '/src/LanguageAccessPagePermissions.php';
require __DIR__ . '/src/LanguageAccessPage.php';

Kirby::plugin('medienbaecker/language-access', [
	'options' => [
		'languages' => [],
		'fieldName' => 'languages',
		'detect'    => false,
		'fallback'  => null,
	],
	'permissions' => [
		'editAll' => true,
	],
	'pageModels' => [
		'default' => Medienbaecker\LanguageAccess\LanguageAccessPage::class,
	],
	'blueprints' => [
		'language-access/users/translator' => __DIR__ . '/blueprints/users/translator.yml',
		'language-access/fields/languages' => __DIR__ . '/blueprints/fields/languages.yml',
		'language-access/site' => function () {
			$options = [];
			if (!LanguageAccessGuard::canEditLanguage()) {
				$options['changeTitle'] = false;
				$options['update'] = false;
			}
			return ['options' => $options];
		},
		'language-access/file' => function () {
			$options = [];
			if (!LanguageAccessGuard::canEditLanguage()) {
				$options['changeName'] = false;
				$options['update'] = false;
			}
			return ['options' => $options];
		},
	],
	'siteMethods' => [
		'enabledLanguages' => function () {
			$enabled = kirby()->option('medienbaecker.language-access.languages');
			return kirby()->languages()->filter(
				fn($lang) => $lang->isDefault() || in_array($lang->code(), $enabled)
			);
		}
	],
	'routes' => [
		// Public-language detect for the home route (opt-in via `detect` option).
		// Runs before Kirby's LanguageRoutes::home() which uses languages.detect
		// against ALL registered languages.
		[
			'pattern' => '',
			'method'  => 'ALL',
			'env'     => 'site',
			'action'  => function () {
				$kirby = kirby();

				if ($kirby->option('medienbaecker.language-access.detect') !== true) {
					return $this->next();
				}
				if (!$kirby->multilang()) {
					return $this->next();
				}

				$language = LanguageAccessDetect::detect($kirby);
				return LanguageAccessDetect::redirect($language->url());
			}
		],

		// Public-language detect for language-less deep URLs (/faq, /blog/post, …).
		// Mirrors Kirby's LanguageRoutes::fallback() behavior: only intercept when
		// the path exists as a page in the default language.
		[
			'pattern' => '(:all)',
			'method'  => 'ALL',
			'env'     => 'site',
			'action'  => function (string $path) {
				$kirby = kirby();

				if ($kirby->option('medienbaecker.language-access.detect') !== true) {
					return $this->next();
				}
				if (!$kirby->multilang()) {
					return $this->next();
				}
				if (F::extension($path) !== '') {
					return $this->next();
				}

				$page = $kirby->page($path);
				if (!$page) {
					return $this->next();
				}

				$language = LanguageAccessDetect::detect($kirby);

				if ($page->translation($language->code())->exists()) {
					return LanguageAccessDetect::redirect($page->url($language->code()));
				}

				return $this->next();
			}
		],

		// Guard: block anonymous access to non-public languages with a real 404.
		// Logged-in users (translators, editors) pass through.
		[
			'pattern'  => '(:all)',
			'language' => '*',
			'method'   => 'ALL',
			'action'   => function () {
				$kirby = kirby();
				if (!$kirby->multilang()) return $this->next();
				if ($kirby->language()->isDefault()) return $this->next();
				if ($kirby->site()->enabledLanguages()->has($kirby->language()->code())) return $this->next();
				if ($kirby->user()) return $this->next();

				return false;
			}
		],
	],
	'hooks' => (function () {
		$writeActions = ['update', 'changeTitle', 'changeSlug', 'changeName'];
		$filter = fn($event) => in_array($event->action(), $writeActions)
			? LanguageAccessGuard::enforce() : null;

		return [
			'page.*:before'          => $filter,
			'file.*:before'          => $filter,
			'site.*:before'          => $filter,
			'language.update:before' => fn($language) => LanguageAccessGuard::enforce($language),
		];
	})(),
]);

// Boot-time validation — warn once about common misconfigurations.
(function () {
	$kirby = kirby();

	if ($kirby->option('medienbaecker.language-access.detect') !== true) {
		return;
	}

	if ($kirby->option('languages.detect') === true) {
		error_log(
			'[language-access] Both languages.detect and medienbaecker.language-access.detect are enabled. '
				. 'Disable languages.detect in your config — the plugin takes over public-language detection.'
		);
	}

	$fallback = $kirby->option('medienbaecker.language-access.fallback');
	if ($fallback === null) {
		return;
	}

	if (!$kirby->language($fallback)) {
		error_log(
			"[language-access] fallback '{$fallback}' is not a registered Kirby language. Using site default."
		);
		return;
	}

	$enabled = (array) $kirby->option('medienbaecker.language-access.languages', []);
	$default = $kirby->defaultLanguage()?->code();
	if ($fallback !== $default && !in_array($fallback, $enabled)) {
		error_log(
			"[language-access] fallback '{$fallback}' is not a public language. Using site default."
		);
	}
})();
