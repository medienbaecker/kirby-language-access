<?php

namespace Medienbaecker\LanguageAccess;

class LanguageAccessGuard
{
	public static function canEditLanguage(): bool
	{
		$kirby = kirby();
		if (!$kirby->multilang()) return true;

		$user = $kirby->user();
		if (!$user) return true;
		if ($user->role()->permissions()->for(
			'medienbaecker.language-access', 'editAll'
		)) return true;

		$lang = $kirby->language();
		if (!$lang) return true;

		$fieldName = $kirby->option(
			'medienbaecker.language-access.fieldName', 'languages'
		);
		$allowed = $user->content()->get($fieldName)->split(',');

		return !$lang->isDefault()
			&& in_array($lang->code(), $allowed);
	}

	public static function enforce($lang = null): void
	{
		$kirby = kirby();
		if (!$kirby->multilang()) return;

		$user = $kirby->user();
		if (!$user) return;
		if ($user->role()->permissions()->for(
			'medienbaecker.language-access', 'editAll'
		)) return;

		$lang ??= $kirby->language();
		if (!$lang) return;

		$fieldName = $kirby->option(
			'medienbaecker.language-access.fieldName', 'languages'
		);
		$allowed = $user->content()->get($fieldName)->split(',');

		if ($lang->isDefault() || !in_array($lang->code(), $allowed)) {
			throw new \Kirby\Exception\PermissionException(
				'You are not allowed to edit ' . $lang->name(),
			);
		}
	}
}
