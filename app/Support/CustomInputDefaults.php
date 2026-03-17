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
        $defaults = $customInput
            ? [
                'field_1' => $customInput->field_1,
                'field_2' => $customInput->field_2,
                'field_select_title' => $customInput->field_select_title,
                'field_select' => $customInput->field_select,
            ]
            : $this->buildDefaults($kategori);

        [$field1Title, $field1Placeholder, $field1Type] = $this->splitField($defaults['field_1'] ?? null);
        [$field2Title, $field2Placeholder, $field2Type] = $this->splitField($defaults['field_2'] ?? null);

        return [
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

    public function syncFromFormState(Kategori $kategori, array $state): CustomInput
    {
        $defaults = $this->buildDefaults($kategori);

        [$defaultField1Title, $defaultField1Placeholder, $defaultField1Type] = $this->splitField($defaults['field_1'] ?? null);
        [$defaultField2Title, $defaultField2Placeholder, $defaultField2Type] = $this->splitField($defaults['field_2'] ?? null);

        $field1Title = $this->sanitizeSegment($this->filledOrDefault($state['field_1_title'] ?? null, $defaultField1Title));
        $field1Placeholder = $this->sanitizeSegment($this->filledOrDefault($state['field_1_placeholder'] ?? null, $defaultField1Placeholder));
        $field1Type = $this->sanitizeSegment($this->filledOrDefault($state['field_1_type'] ?? null, $defaultField1Type ?: 'text'));

        $hasExplicitField2Configuration = filled($state['field_2_title'] ?? null)
            || filled($state['field_2_placeholder'] ?? null)
            || filled($state['field_2_type'] ?? null)
            || filled($state['field_select_title_input'] ?? null)
            || filled($state['field_select_value_input'] ?? null)
            || ($state['has_field_2'] ?? null) === true;

        $hasField2 = $hasExplicitField2Configuration
            ? (bool) ($state['has_field_2'] ?? false)
            : filled($defaults['field_2'] ?? null);

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
