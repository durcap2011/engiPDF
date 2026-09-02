<?php
declare(strict_types=1);

namespace EngiPDF\Template;

class PlaceholderResolver
{
    public function resolve(string $text, array $data): string
    {
        return preg_replace_callback(
            '/\{\{\s*(.+?)\s*\}\}/',
            fn (array $matches): string => $this->evaluate($matches[1], $data),
            $text
        );
    }

    private function evaluate(string $expression, array $data): string
    {
        if (str_contains($expression, '|')) {
            [$var, $filter] = array_map('trim', explode('|', $expression, 2));
            $value = $this->resolveVariable($var, $data);
            return $this->applyFilter($value, $filter);
        }

        return $this->resolveVariable($expression, $data);
    }

    private function resolveVariable(string $path, array $data): string
    {
        $parts = explode('.', trim($path));
        $current = $data;

        foreach ($parts as $part) {
            if (!is_array($current) || !array_key_exists($part, $current)) {
                return '';
            }
            $current = $current[$part];
        }

        return (string) $current;
    }

    private function applyFilter(mixed $value, string $filter): string
    {
        if (str_starts_with($filter, 'currency')) {
            return number_format((float) $value, 2, ',', '.');
        }

        if (str_starts_with($filter, 'date')) {
            $format = 'd/m/Y';
            if (preg_match('/date:\s*["\'](.+?)["\']/', $filter, $m)) {
                $format = $m[1];
            }
            return date($format, strtotime((string) $value));
        }

        if (str_starts_with($filter, 'number')) {
            $decimals = 0;
            if (preg_match('/number:\s*(\d+)/', $filter, $m)) {
                $decimals = (int) $m[1];
            }
            return number_format((float) $value, $decimals, ',', '.');
        }

        return (string) $value;
    }
}
