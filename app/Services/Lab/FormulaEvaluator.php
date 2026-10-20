<?php

declare(strict_types=1);

namespace App\Services\Lab;

/**
 * Evaluates a calculated parameter formula such as "{TC} - {HDL} - {TG} / 5"
 * without eval(). Mirrors resources/js/lib/flags.js: only numbers, the four
 * operators and parentheses are allowed, and a missing input yields null.
 */
final class FormulaEvaluator
{
    private int $position = 0;

    private string $input = '';

    /**
     * @param  callable(string): ?string  $valueForCode  numeric string for a parameter code, null when missing
     */
    public function evaluate(string $formula, callable $valueForCode): ?float
    {
        $missing = false;

        $expression = preg_replace_callback(
            '/\{([A-Za-z0-9_]+)\}/',
            function (array $match) use ($valueForCode, &$missing): string {
                $value = $valueForCode($match[1]);

                if ($value === null || preg_match('/^-?\d+(\.\d+)?$/', $value) !== 1) {
                    $missing = true;

                    return '0';
                }

                return $value;
            },
            $formula,
        ) ?? $formula;

        if ($missing || $expression === '' || preg_match('/^[\d\s.+\-*\/()]+$/', $expression) !== 1) {
            return null;
        }

        return $this->parse($expression);
    }

    private function parse(string $expression): ?float
    {
        $this->input = str_replace(' ', '', $expression);
        $this->position = 0;

        try {
            $value = $this->parseSum();

            return $this->position === strlen($this->input) ? $value : null;
        } catch (\InvalidArgumentException) {
            return null;
        }
    }

    private function parseSum(): float
    {
        $value = $this->parseProduct();

        while ($this->peek() === '+' || $this->peek() === '-') {
            $operator = $this->next();
            $right = $this->parseProduct();
            $value = $operator === '+' ? $value + $right : $value - $right;
        }

        return $value;
    }

    private function parseProduct(): float
    {
        $value = $this->parseUnary();

        while ($this->peek() === '*' || $this->peek() === '/') {
            $operator = $this->next();
            $right = $this->parseUnary();

            if ($operator === '/' && $right === 0.0) {
                throw new \InvalidArgumentException('Division by zero.');
            }

            $value = $operator === '*' ? $value * $right : $value / $right;
        }

        return $value;
    }

    private function parseUnary(): float
    {
        if ($this->peek() === '-') {
            $this->next();

            return -$this->parseUnary();
        }

        if ($this->peek() === '+') {
            $this->next();

            return $this->parseUnary();
        }

        return $this->parsePrimary();
    }

    private function parsePrimary(): float
    {
        if ($this->peek() === '(') {
            $this->next();
            $value = $this->parseSum();

            if ($this->next() !== ')') {
                throw new \InvalidArgumentException('Unbalanced parenthesis.');
            }

            return $value;
        }

        $digits = '';

        while (($char = $this->peek()) !== null && (ctype_digit($char) || $char === '.')) {
            $digits .= $this->next();
        }

        if ($digits === '' || ! is_numeric($digits)) {
            throw new \InvalidArgumentException('Number expected.');
        }

        return (float) $digits;
    }

    private function peek(): ?string
    {
        return $this->position < strlen($this->input) ? $this->input[$this->position] : null;
    }

    private function next(): string
    {
        $char = $this->peek();

        if ($char === null) {
            throw new \InvalidArgumentException('Unexpected end of formula.');
        }

        $this->position++;

        return $char;
    }
}
