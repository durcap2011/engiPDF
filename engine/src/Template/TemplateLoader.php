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

        if (!isset($data['version'], $data['page'], $data['elements'])) {
            throw new \RuntimeException("Template JSON non valido: mancano campi obbligatori");
        }

        return $data;
    }
}
