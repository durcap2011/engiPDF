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
            $parts = array_map('trim', explode('|', $expression));
            $var = array_shift($parts);
            $value = $this->resolveVariable($var, $data);

            foreach ($parts as $filter) {
                $value = $this->applyFilter($value, $filter, $data);
            }

            return $value;
        }

        return $this->resolveVariable($expression, $data);
    }

    private function resolveVariable(string $path, array $data): string
    {
        $path = trim($path);

        if (preg_match('/^(.+?)\[(\d+)\]\.?(.*)$/', $path, $m)) {
            $base = $m[1];
            $index = (int) $m[2];
            $rest = $m[3];

            $arr = $this->resolveRaw($base, $data);
            if (!is_array($arr) || !isset($arr[$index])) {
                return '';
            }

            $current = $arr[$index];
            if ($rest !== '' && is_array($current)) {
                return (string) $this->resolveRaw($rest, $current);
            }

            return (string) $current;
        }

        $result = $this->resolveRaw($path, $data);
        return $result === null ? '' : (string) $result;
    }

    private function resolveRaw(string $path, array $data): mixed
    {
        $parts = explode('.', trim($path));
        $current = $data;

        foreach ($parts as $part) {
            if (!is_array($current) || !array_key_exists($part, $current)) {
                return null;
            }
            $current = $current[$part];
        }

        return $current;
    }

    private function applyFilter(mixed $value, string $filter, array $data): string
    {
        $str = (string) $value;

        if (str_starts_with($filter, 'currency')) {
            return number_format((float) $value, 2, ',', '.');
        }

        if (str_starts_with($filter, 'date')) {
            $format = 'd/m/Y';
            if (preg_match('/date:\s*["\'](.+?)["\']/', $filter, $m)) {
                $format = $m[1];
            }
            return date($format, strtotime($str));
        }

        if (str_starts_with($filter, 'number')) {
            $decimals = 0;
            if (preg_match('/number:\s*(\d+)/', $filter, $m)) {
                $decimals = (int) $m[1];
            }
            return number_format((float) $value, $decimals, ',', '.');
        }

        if ($filter === 'uppercase') {
            return mb_strtoupper($str);
        }

        if ($filter === 'lowercase') {
            return mb_strtolower($str);
        }

        if (str_starts_with($filter, 'truncate')) {
            $len = 50;
            if (preg_match('/truncate:\s*(\d+)/', $filter, $m)) {
                $len = (int) $m[1];
            }
            if (mb_strlen($str) <= $len) {
                return $str;
            }
            return mb_substr($str, 0, $len) . '...';
        }

        if (str_starts_with($filter, 'default')) {
            $default = '';
            if (preg_match('/default:\s*["\'](.+?)["\']/', $filter, $m)) {
                $default = $m[1];
            }
            return $str !== '' ? $str : $default;
        }

        if (str_starts_with($filter, 'if')) {
            if (preg_match('/if:\s*["\'](.+?)["\']\s*:\s*["\'](.+?)["\']/', $filter, $m)) {
                return $str === $m[1] ? $m[2] : $m[3] ?? '';
            }
            return $str;
        }

        if ($filter === 'sum') {
            if (is_array($value)) {
                $total = 0;
                foreach ($value as $v) {
                    $total += (float) $v;
                }
                return number_format($total, 2, ',', '.');
            }
            return $str;
        }

        if (str_starts_with($filter, 'capitalize')) {
            return mb_convert_case($str, MB_CASE_TITLE);
        }

        if (str_starts_with($filter, 'trim')) {
            return trim($str);
        }

        if (str_starts_with($filter, 'len')) {
            return (string) mb_strlen($str);
        }

        return $str;
    }
}
