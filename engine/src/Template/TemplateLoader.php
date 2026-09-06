<?php
declare(strict_types=1);

namespace EngiPDF\Template;

class TemplateLoader
{
    public function loadFromFile(string $path): array
    {
        if (!file_exists($path)) {
            throw new \RuntimeException("Template non trovato: $path");
        }

        $json = file_get_contents($path);
        return $this->parse($json);
    }

    public function loadFromString(string $json): array
    {
        return $this->parse($json);
    }

    private function parse(string $json): array
    {
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        $hasPages = isset($data['pages']) && is_array($data['pages']);
        $hasLegacy = isset($data['version'], $data['page'], $data['elements']);

        if (!$hasPages && !$hasLegacy) {
            throw new \RuntimeException("Template JSON non valido: mancano campi obbligatori");
        }

        if ($hasPages && !isset($data['elements'])) {
            $data['elements'] = [];
        }

        return $data;
    }
}
