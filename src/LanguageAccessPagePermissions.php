<?php

namespace Medienbaecker\LanguageAccess;

use Kirby\Cms\PagePermissions;

class LanguageAccessPagePermissions extends PagePermissions
{
	protected function canUpdate(): bool
	{
		return LanguageAccessGuard::canEditLanguage();
	}

	protected function canChangeTitle(): bool
	{
		return LanguageAccessGuard::canEditLanguage();
	}

	protected function canChangeSlug(): bool
	{
		return LanguageAccessGuard::canEditLanguage();
	}
}
