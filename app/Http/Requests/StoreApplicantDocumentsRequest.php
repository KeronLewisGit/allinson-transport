<?php

namespace App\Http\Requests;

use App\Enums\ApplicantDocumentKind;
use App\Http\Requests\Concerns\HoldsUploads;
use App\Models\ApplicantDocument;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/** The applicant's signed documents page: one photo or scan per card, at least one file. */
class StoreApplicantDocumentsRequest extends FormRequest
{
    use HoldsUploads;

    public static function heldForm(): string
    {
        return 'applicant-documents';
    }

    protected function heldFields(): array
    {
        $fields = [];
        foreach (ApplicantDocumentKind::cases() as $kind) {
            $fields['docs.'.$kind->value] = false;
        }

        return $fields;
    }

    public function authorize(): bool
    {
        return true;
    }

    #[\Override]
    protected function prepareForValidation(): void
    {
        $this->restoreHeldUploads();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $rules = ['docs' => ['nullable', 'array']];
        foreach (ApplicantDocumentKind::cases() as $kind) {
            $rules['docs.'.$kind->value] = ['nullable', 'file', 'max:8192', 'mimes:'.implode(',', ApplicantDocument::FILE_TYPES)];
        }

        return $rules;
    }

    /** @return array<int, \Closure> */
    public function after(): array
    {
        return [function (Validator $v): void {
            if (array_filter((array) $this->file('docs', [])) === []) {
                $v->errors()->add('docs', 'Choose at least one photo or scan to upload.');
            }
        }];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        $m = [];
        foreach (ApplicantDocumentKind::cases() as $kind) {
            $m['docs.'.$kind->value.'.max'] = $kind->label().': the file must be 8 MB or smaller.';
            $m['docs.'.$kind->value.'.mimes'] = $kind->label().': upload a photo (JPG, PNG or HEIC) or a PDF.';
            $m['docs.'.$kind->value.'.uploaded'] = $kind->label().': the file could not be uploaded. It must be 8 MB or smaller.';
        }

        return $m;
    }
}
