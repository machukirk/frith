<?php

namespace App\Support;

/**
 * The words for one screen of a form, ready for a view.
 *
 * Thin on purpose: views ask for a heading or a field's label and get a string,
 * without knowing whether it came from the database or the config floor.
 */
class StepContent
{
    public function __construct(
        private readonly string $form,
        private readonly string $step,
    ) {}

    public static function for(string $step, string $form = Taxonomy::FORM): self
    {
        return new self($form, $step);
    }

    public function heading(): string
    {
        return $this->step()['heading'] ?? '';
    }

    public function standfirst(): ?string
    {
        return $this->step()['standfirst'] ?? null;
    }

    public function isPrivate(): bool
    {
        return (bool) ($this->step()['is_private'] ?? false);
    }

    public function label(string $field, string $fallback = ''): string
    {
        return $this->field($field)['label'] ?? $fallback;
    }

    public function help(string $field): ?string
    {
        return $this->field($field)['help'] ?? null;
    }

    public function placeholder(string $field): ?string
    {
        return $this->field($field)['placeholder'] ?? null;
    }

    /** @return array<string, mixed> */
    private function step(): array
    {
        return FormDefinition::for($this->form)['steps'][$this->step] ?? [];
    }

    /** @return array<string, mixed> */
    private function field(string $key): array
    {
        return $this->step()['fields'][$key] ?? [];
    }
}
