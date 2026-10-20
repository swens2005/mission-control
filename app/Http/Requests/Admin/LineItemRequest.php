<?php

namespace App\Http\Requests\Admin;

use App\Enums\LineItemKind;
use App\Rules\Price;
use App\Rules\QuarterQuantity;
use App\Support\Money\Money;
use App\Support\Money\Quantity;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LineItemRequest extends FormRequest
{
    /**
     * Authorization happens in the controller via LineItemPolicy.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:500'],
            'kind' => ['required', Rule::enum(LineItemKind::class)],
            'unit_price' => ['required', new Price],
            'default_quantity' => ['required', new QuarterQuantity(wholeOnly: $this->input('kind') === LineItemKind::Fixed->value)],
        ];
    }

    /**
     * The validated item as model attributes, money in cents.
     *
     * @return array{name: string, description: string|null, kind: LineItemKind, unit_price_cents: int, default_quantity: int}
     */
    public function item(): array
    {
        return [
            'name' => $this->string('name')->trim()->value(),
            'description' => $this->filled('description') ? $this->string('description')->trim()->value() : null,
            'kind' => $this->enum('kind', LineItemKind::class) ?? LineItemKind::Fixed,
            'unit_price_cents' => (int) Money::parse($this->string('unit_price')->value()),
            'default_quantity' => (int) Quantity::parse($this->string('default_quantity')->value()),
        ];
    }
}
