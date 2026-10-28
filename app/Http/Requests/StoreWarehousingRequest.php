<?php

namespace App\Http\Requests;

use App\Enums\GoodsType;
use App\Enums\StorageDuration;
use App\Models\WarehousingEnquiry;
use App\Rules\UkPhone;
use App\Support\Depots;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWarehousingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    #[\Override]
    protected function prepareForValidation(): void
    {
        $this->merge(['email' => strtolower(input_string($this->input('email'))), 'hazardous' => $this->boolean('hazardous')]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'company' => ['required', 'string', 'max:160'],
            'contact_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'phone' => ['required', 'string', 'max:30', new UkPhone],
            'pallet_spaces' => ['required', 'integer', 'min:1', 'max:20000'],
            'duration' => ['required', Rule::enum(StorageDuration::class)],
            'start_on' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:today'],
            'goods_type' => ['required', Rule::enum(GoodsType::class)],
            'goods_description' => ['required', 'string', 'max:2000'],
            'handling' => ['nullable', 'array'],
            'handling.*' => ['string', Rule::in(array_keys(WarehousingEnquiry::HANDLING))],
            'hazardous' => ['boolean'],
            'depot' => ['nullable', Rule::in(Depots::keys())],
            'message' => ['nullable', 'string', 'max:3000'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['contact_name' => 'your name', 'pallet_spaces' => 'number of pallet spaces', 'goods_type' => 'type of goods', 'goods_description' => 'description of the goods', 'start_on' => 'start date'];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['duration.required' => 'Choose roughly how long you need the space.', 'goods_type.required' => 'Choose the kind of goods.'];
    }
}
