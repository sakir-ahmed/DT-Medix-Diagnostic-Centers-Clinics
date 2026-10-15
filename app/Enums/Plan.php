<?php

declare(strict_types=1);

namespace App\Enums;

enum Plan: string
{
    case Starter = 'starter';
    case Growth = 'growth';
    case Pro = 'pro';

    /**
     * @return array{branches: int|null, users: int|null}
     */
    public function limits(): array
    {
        /** @var array{branches: int|null, users: int|null} $limits */
        $limits = config("tenancy.plans.{$this->value}.limits");

        return $limits;
    }

    /**
     * @return list<string>
     */
    public function modules(): array
    {
        /** @var list<string> $modules */
        $modules = config("tenancy.plans.{$this->value}.modules", []);

        return $modules === ['*'] ? Module::values() : $modules;
    }

    public function price(): string
    {
        return (string) config("tenancy.plans.{$this->value}.price");
    }
}
