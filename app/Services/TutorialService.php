<?php

namespace App\Services;

use Illuminate\Support\Str;

class TutorialService
{
    /**
     * Get the tutorial context data for a given route name.
     *
     * @param string|null $routeName
     * @return array
     */
    public function getContextForRoute(?string $routeName): array
    {
        $contexts = config('tutorial.contexts', []);

        if (!$routeName) {
            return $this->getDefaultContext();
        }

        foreach ($contexts as $pattern => $context) {
            if (Str::is($pattern, $routeName)) {
                return $context;
            }
        }

        return $this->getDefaultContext();
    }

    /**
     * Fallback default context if no route matches.
     *
     * @return array
     */
    protected function getDefaultContext(): array
    {
        return config('tutorial.contexts.*', [
            'title' => 'tutorial.help_tutorial',
            'icon'  => 'ti-help',
            'desc'  => 'tutorial.need_help_navigating',
            'steps' => [
                'tutorial.general_navigation',
                'tutorial.general_settings',
                'tutorial.general_creating',
            ],
        ]);
    }
}
