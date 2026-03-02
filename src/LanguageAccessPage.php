<?php

namespace Medienbaecker\LanguageAccess;

use Kirby\Cms\Page;

class LanguageAccessPage extends Page
{
	public function permissions(): LanguageAccessPagePermissions
	{
		return new LanguageAccessPagePermissions($this);
	}
}
