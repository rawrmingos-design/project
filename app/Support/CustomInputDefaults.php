<?php

namespace App\Support;

use App\Models\CustomInput;
use App\Models\Kategori;
use Illuminate\Support\Facades\Schema;

class CustomInputDefaults
{
    public function buildDefaults(Kategori $kategori): array
    {
        return match ($kategori->tipe) {
            'game', 'voucher', 'populer' => [
                'field_1' => 'User ID,Masukkan User ID,number',
                'field_2' => 'Server ID,Masukkan Server ID,number',
                'field_select_title' => null,
                'field_select' => null,
            ],
            'pulsa', 'app', 'data' => [
                'field_1' => 'No. HP,Masukkan No. HP,number',
                'field_2' => null,
                'field_select_title' => null,
                'field_select' => null,
            ],
            default => [
                'field_1' => 'User ID,Masukkan User ID,text',
                'field_2' => null,
                'field_select_title' => null,
                'field_select' => null,
            ],
        };
    }

    public function ensureExists(Kategori $kategori): bool
    {
        if (! Schema::hasTable('custom_inputs')) {
            return false;
        }

        if (CustomInput::query()->where('kategori_id', $kategori->id)->exists()) {
            return false;
        }

        CustomInput::query()->create([
            'kategori_id' => $kategori->id,
            ...$this->buildDefaults($kategori),
        ]);

        return true;
    }

    public function getFormState(Kategori $kategori): array
    {
        $customInput = $kategori->customInput;
        $fallbackDefaults = $this->buildDefaults($kategori);
        $defaults = $customInput
            ? [
                'field_1' => $customInput->field_1,
                'field_2' => $customInput->field_2,
                'field_select_title' => $customInput->field_select_title,
                'field_select' => $customInput->field_select,
            ]
            : $fallbackDefaults;

        [$field1Title, $field1Placeholder, $field1Type] = $this->splitField($defaults['field_1'] ?? null);
        [$field2Title, $field2Placeholder, $field2Type] = $this->splitField($defaults['field_2'] ?? null);

        $useCustomField1 = $customInput
            ? trim((string) ($defaults['field_1'] ?? '')) !== trim((string) ($fallbackDefaults['field_1'] ?? ''))
            : false;

        return [
            'use_custom_field_1' => $useCustomField1,
            'field_1_title' => $field1Title,
            'field_1_placeholder' => $field1Placeholder,
            'field_1_type' => $field1Type ?: 'text',
            'has_field_2' => filled($defaults['field_2'] ?? null),
            'field_2_title' => $field2Title,
            'field_2_placeholder' => $field2Placeholder,
            'field_2_type' => $field2Type ?: 'text',
            'field_select_title_input' => $defaults['field_select_title'],
            'field_select_value_input' => $defaults['field_select'],
        ];
    }

    /**
     * @return array{user_id: array{label: string, placeholder: string, type: string}, zone: array{label: string, placeholder: string, type: string, is_select: bool, options: array<int, array{label: string, value: string}>}|null}
     */
    public function inputSpecification(Kategori $kategori): array
    {
        $customInput = CustomInput::query()
            ->where('kategori_id', (string) $kategori->id)
            ->first();
        $fallbackDefaults = $this->buildDefaults($kategori);
        $defaults = $customInput
            ? [
                'field_1' => $customInput->field_1,
                'field_2' => $customInput->field_2,
                'field_select_title' => $customInput->field_select_title,
                'field_select' => $customInput->field_select,
            ]
            : $fallbackDefaults;
        [$field1Title, $field1Placeholder, $field1Type] = $this->splitField($defaults['field_1'] ?? null);
        [$field2Title, $field2Placeholder, $field2Type] = $this->splitField($defaults['field_2'] ?? null);
        $state = [
            'field_1_title' => $field1Title,
            'field_1_placeholder' => $field1Placeholder,
            'field_1_type' => $field1Type ?: 'text',
            'has_field_2' => filled($defaults['field_2'] ?? null),
            'field_2_title' => $field2Title,
            'field_2_placeholder' => $field2Placeholder,
            'field_2_type' => $field2Type ?: 'text',
            'field_select_title_input' => $defaults['field_select_title'] ?? null,
            'field_select_value_input' => $defaults['field_select'] ?? null,
        ];
        $zoneType = strtolower(trim((string) $state['field_2_type']));

        return [
            'user_id' => [
                'label' => trim((string) $state['field_1_title']) ?: 'User ID',
                'placeholder' => trim((string) $state['field_1_placeholder']) ?: 'Masukkan User ID',
                'type' => trim((string) $state['field_1_type']) ?: 'text',
            ],
            'zone' => $state['has_field_2'] ? [
                'label' => trim((string) $state['field_2_title']) ?: 'Server / Zone',
                'placeholder' => trim((string) $state['field_2_placeholder']) ?: 'Masukkan Server / Zone',
                'type' => $zoneType ?: 'text',
                'is_select' => $zoneType === 'select',
                'options' => $this->selectOptions(
                    $state['field_select_title_input'] ?? null,
                    $state['field_select_value_input'] ?? null,
                ),
            ] : null,
        ];
    }

    public function syncFromFormState(Kategori $kategori, array $state): CustomInput
    {
        $defaults = $this->buildDefaults($kategori);

        [$defaultField1Title, $defaultField1Placeholder, $defaultField1Type] = $this->splitField($defaults['field_1'] ?? null);
        [$defaultField2Title, $defaultField2Placeholder, $defaultField2Type] = $this->splitField($defaults['field_2'] ?? null);

        $useCustomField1 = filter_var($state['use_custom_field_1'] ?? false, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
        $useCustomField1 = $useCustomField1 ?? false;

        if ($useCustomField1) {
            $field1Title = $this->sanitizeSegment($this->filledOrDefault($state['field_1_title'] ?? null, $defaultField1Title));
            $field1Placeholder = $this->sanitizeSegment($this->filledOrDefault($state['field_1_placeholder'] ?? null, $defaultField1Placeholder));
            $field1Type = $this->sanitizeSegment($this->filledOrDefault($state['field_1_type'] ?? null, $defaultField1Type ?: 'text'));
        } else {
            $field1Title = $this->sanitizeSegment($defaultField1Title);
            $field1Placeholder = $this->sanitizeSegment($defaultField1Placeholder);
            $field1Type = $this->sanitizeSegment($defaultField1Type ?: 'text');
        }

        $hasField2Key = array_key_exists('has_field_2', $state);

        $hasExplicitField2Configuration = filled($state['field_2_title'] ?? null)
            || filled($state['field_2_placeholder'] ?? null)
            || filled($state['field_2_type'] ?? null)
            || filled($state['field_select_title_input'] ?? null)
            || filled($state['field_select_value_input'] ?? null)
            || ($state['has_field_2'] ?? null) === true
            || $hasField2Key;

        if ($hasField2Key) {
            $hasField2 = filter_var($state['has_field_2'], FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
            $hasField2 = $hasField2 ?? false;
        } else {
            $hasField2 = $hasExplicitField2Configuration
                ? (bool) ($state['has_field_2'] ?? false)
                : filled($defaults['field_2'] ?? null);
        }

        $payload = [
            'field_1' => implode(',', [$field1Title, $field1Placeholder, $field1Type]),
            'field_2' => null,
            'field_select_title' => null,
            'field_select' => null,
        ];

        if ($hasField2) {
            $field2Title = $this->sanitizeSegment($this->filledOrDefault($state['field_2_title'] ?? null, $defaultField2Title));
            $field2Placeholder = $this->sanitizeSegment($this->filledOrDefault($state['field_2_placeholder'] ?? null, $defaultField2Placeholder));
            $field2Type = $this->sanitizeSegment($this->filledOrDefault($state['field_2_type'] ?? null, $defaultField2Type ?: 'text'));

            $payload['field_2'] = implode(',', [$field2Title, $field2Placeholder, $field2Type]);

            if ($field2Type === 'select') {
                $payload['field_select_title'] = $this->normalizeOptions($state['field_select_title_input'] ?? null);
                $payload['field_select'] = $this->normalizeOptions($state['field_select_value_input'] ?? null);
            }
        }

        return CustomInput::query()->updateOrCreate(
            ['kategori_id' => $kategori->id],
            $payload,
        );
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    private function selectOptions(?string $titles, ?string $values): array
    {
        $titles = $this->optionSegments($titles);
        $values = $this->optionSegments($values);

        return collect($titles)->map(function (string $title, int $index) use ($values): array {
            return [
                'label' => $title,
                'value' => $values[$index] ?? $title,
            ];
        })->values()->all();
    }

    /**
     * @return array<int, string>
     */
    private function optionSegments(?string $value): array
    {
        $items = preg_split('/[\r\n,]+/', (string) $value) ?: [];

        return array_values(array_filter(array_map('trim', $items), static fn (string $item): bool => $item !== ''));
    }

    private function splitField(?string $value): array
    {
        $parts = array_map(
            static fn (?string $part): ?string => $part === null ? null : trim($part),
            array_pad(explode(',', (string) $value, 3), 3, null),
        );

        return [$parts[0], $parts[1], $parts[2]];
    }

    private function normalizeOptions(?string $value): ?string
    {
        $items = preg_split('/[\r\n,]+/', (string) $value) ?: [];
        $items = array_values(array_filter(array_map('trim', $items), static fn (string $item): bool => $item !== ''));

        return $items === [] ? null : implode(',', $items);
    }

    private function sanitizeSegment(?string $value): string
    {
        $sanitized = str_replace(',', ' ', trim((string) $value));

        return $sanitized !== '' ? $sanitized : '-';
    }

    private function filledOrDefault(?string $value, ?string $default): ?string
    {
        return filled(trim((string) $value)) ? $value : $default;
    }
}
