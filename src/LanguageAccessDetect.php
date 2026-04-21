<?php

namespace Medienbaecker\LanguageAccess;

use Kirby\Cms\App;
use Kirby\Cms\Language;
use Kirby\Cms\Response;
use Kirby\Toolkit\Str;

class LanguageAccessDetect
{
	public static function detect(App $kirby): Language
	{
		$public = $kirby->site()->enabledLanguages();

		foreach ($kirby->visitor()->acceptedLanguages() as $accepted) {
			$code   = $accepted->code();
			$locale = $accepted->locale();

			$match = $public->filter(fn ($language) =>
				Str::substr($language->locale(LC_ALL), 0, 5) === Str::substr($locale, 0, 5)
			)->first();
			if ($match) return $match;

			$match = $public->findBy('code', $code);
			if ($match) return $match;

			$match = $public->filter(fn ($language) =>
				Str::substr($language->locale(LC_ALL), 0, 2) === Str::substr($locale, 0, 2)
			)->first();
			if ($match) return $match;
		}

		return static::fallback($kirby);
	}

	public static function fallback(App $kirby): Language
	{
		$code = $kirby->option('medienbaecker.language-access.fallback');

		if ($code && $language = $kirby->language($code)) {
			if (
				$language->isDefault() ||
				in_array($code, (array) $kirby->option('medienbaecker.language-access.languages', []))
			) {
				return $language;
			}
		}

		return $kirby->defaultLanguage();
	}

	public static function redirect(string $url): Response
	{
		return new Response([
			'code'    => 302,
			'headers' => [
				'Location' => $url,
				'Vary'     => 'Accept-Language',
			],
		]);
	}
}
