<?php

use Kirby\Cms\App as Kirby;
use Medienbaecker\LanguageAccess\LanguageAccessGuard;

require __DIR__ . '/src/LanguageAccessGuard.php';
require __DIR__ . '/src/LanguageAccessPagePermissions.php';
require __DIR__ . '/src/LanguageAccessPage.php';

Kirby::plugin('medienbaecker/language-access', [
	'options' => [
		'languages' => [],
		'fieldName' => 'languages',
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
		[
			'pattern' => '(:all)',
			'language' => '*',
			'action' => function () {
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
