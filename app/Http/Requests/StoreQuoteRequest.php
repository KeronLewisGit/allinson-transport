<?php

namespace App\Http\Requests;

use App\Enums\QuoteService;
use App\Http\Requests\Concerns\HoldsUploads;
use App\Models\Quote;
use App\Rules\UkPhone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreQuoteRequest extends FormRequest
{
    use HoldsUploads;

    public static function heldForm(): string
    {
        return 'quote';
    }

    protected function heldFields(): array
    {
        return ['files' => true];
    }

    public function authorize(): bool
    {
        return true;
    }

    #[\Override]
    protected function prepareForValidation(): void
    {
        $this->restoreHeldUploads();
        $merge = ['email' => strtolower(input_string($this->input('email')))];
        foreach (['collection_postcode', 'delivery_postcode'] as $field) {
            $raw = input_string($this->input($field));
            $merge[$field] = uk_postcode($raw) ?? strtoupper($raw);
        }
        foreach (['adr', 'tail_lift', 'regular'] as $flag) {
            $merge[$flag] = $this->boolean($flag);
        }
        $this->merge($merge);
    }

    public function service(): ?QuoteService
    {
        return QuoteService::tryFrom(input_string($this->input('service')));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $service = $this->service();
        $postcode = ['required', 'string', 'max:10', 'regex:/^[A-Z]{1,2}\d[A-Z\d]? \d[A-Z]{2}$/'];

        return [
            'service' => ['required', Rule::enum(QuoteService::class)],
            'company' => ['nullable', 'string', 'max:160'],
            'contact_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'phone' => ['required', 'string', 'max:30', new UkPhone],
            'collection_postcode' => $postcode,
            'collection_town' => ['nullable', 'string', 'max:80'],
            'collection_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today', 'before:+1 year'],
            'delivery_postcode' => $postcode,
            'delivery_town' => ['nullable', 'string', 'max:80'],
            'delivery_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:collection_date'],
            'goods' => ['required', 'string', 'max:300'],
            'pallets' => [$service?->countsPallets() ? 'required' : 'nullable', 'integer', 'min:1', 'max:52'],
            'weight_kg' => ['required', 'integer', 'min:1', 'max:150000'],
            'length_m' => [$service?->needsDimensions() ? 'required' : 'nullable', 'numeric', 'min:0.1', 'max:60'],
            'width_m' => [$service?->needsDimensions() ? 'required' : 'nullable', 'numeric', 'min:0.1', 'max:10'],
            'height_m' => [$service?->needsDimensions() ? 'required' : 'nullable', 'numeric', 'min:0.1', 'max:10'],
            'adr' => ['boolean'],
            'adr_details' => ['nullable', 'required_if:adr,true', 'string', 'max:160'],
            'tail_lift' => ['boolean'],
            'regular' => ['boolean'],
            'message' => ['nullable', 'string', 'max:3000'],
            'files' => ['nullable', 'array', 'max:'.Quote::MAX_FILES],
            'files.*' => ['file', 'max:10240', 'mimes:'.implode(',', Quote::FILE_TYPES)],
        ];
    }

    /** @return array<int, \Closure> */
    public function after(): array
    {
        return [function (Validator $v): void {
            if ($this->service() === QuoteService::Pallets && (int) $this->input('pallets') > 26) {
                $v->errors()->add('pallets', 'Over 26 pallets is usually a full load. Choose "Full load", or call us on '.ga('phone').'.');
            }
        }];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'contact_name' => 'your name', 'collection_postcode' => 'collection postcode', 'delivery_postcode' => 'delivery postcode',
            'collection_date' => 'collection date', 'delivery_date' => 'delivery date', 'weight_kg' => 'total weight',
            'length_m' => 'length', 'width_m' => 'width', 'height_m' => 'height', 'adr_details' => 'UN number and class', 'files.*' => 'attachment',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'service.required' => 'Choose the kind of load.',
            'collection_postcode.regex' => 'Enter the full collection postcode, for example DL3 0PH.',
            'delivery_postcode.regex' => 'Enter the full delivery postcode, for example L1 8JQ.',
            'collection_date.after_or_equal' => 'The collection date cannot be in the past.',
            'delivery_date.after_or_equal' => 'The delivery date must be on or after the collection date.',
            'pallets.required' => 'Tell us how many pallets.',
            'length_m.required' => 'Hiab and abnormal loads need the length, width and height.',
            'width_m.required' => 'Hiab and abnormal loads need the length, width and height.',
            'height_m.required' => 'Hiab and abnormal loads need the length, width and height.',
            'adr_details.required_if' => 'For dangerous goods, give the UN number and class, for example UN1263, class 3.',
            'files.max' => 'Attach up to '.Quote::MAX_FILES.' files.',
            'files.*.max' => 'Each attachment must be 10 MB or smaller.',
            'files.*.mimes' => 'Attachments must be PDF, photos (JPG or PNG), Word or Excel files.',
            'files.*.uploaded' => 'An attachment could not be uploaded. Each must be 10 MB or smaller.',
        ];
    }
}
