<?php

namespace App\Services\Email;

use InvalidArgumentException;

class EmailTemplateRegistry
{
    public function getProviderTemplateId(string $templateKey): string
    {
        $templateId = config("services.brevo.templates.{$templateKey}");

        if (blank($templateId)) {
            throw new InvalidArgumentException("Brevo template ID is not configured for [{$templateKey}].");
        }

        return (string) $templateId;
    }
}
