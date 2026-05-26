<?php

declare(strict_types=1);

namespace App\Application\Documents;

final class TemplateRenderer
{
    /**
     * @param  array<string, string>  $variables
     */
    public function render(string $htmlBody, array $variables): string
    {
        $rendered = $htmlBody;

        foreach ($variables as $key => $value) {
            $rendered = str_replace('{{' . $key . '}}', $value, $rendered);
        }

        return $rendered;
    }
}
