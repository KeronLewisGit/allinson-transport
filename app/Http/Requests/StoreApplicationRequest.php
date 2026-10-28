<?php

namespace App\Http\Requests;

use App\Enums\JobRole;
use App\Http\Requests\Concerns\HoldsUploads;
use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Rules\UkPhone;
use App\Support\Depots;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * The online application that replaces the 2012 Word form. Driving roles declare their
 * licence categories, Driver CPC and tacho card expiry dates and endorsements; office roles
 * attach a CV.
 */
class StoreApplicationRequest extends FormRequest
{
    use HoldsUploads;

    public static function heldForm(): string
    {
        return 'application';
    }

    protected function heldFields(): array
    {
        return ['cv' => false];
    }

    public function authorize(): bool
    {
        return true;
    }

    #[\Override]
    protected function prepareForValidation(): void
    {
        $this->restoreHeldUploads();
        // When an opening is picked, its role and depot are the truth.
        $jobId = input_string($this->input('job_posting_id'));
        $job = ctype_digit($jobId) ? JobPosting::open()->find((int) $jobId) : null;
        if ($job) {
            $this->merge(['role' => $job->role->value, 'depot' => $job->depot]);
        }
        $postcode = input_string($this->input('postcode'));
        $this->merge([
            'email' => $this->filled('email') ? strtolower(input_string($this->input('email'))) : null,
            'postcode' => $postcode === '' ? null : (uk_postcode($postcode) ?? strtoupper($postcode)),
            'right_to_work' => $this->boolean('right_to_work'),
        ]);
    }

    public function role(): ?JobRole
    {
        return JobRole::tryFrom(input_string($this->input('role')));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $role = $this->role();
        $drives = $role?->drives() ?? false;
        $card = [$drives ? 'required' : 'nullable', 'date_format:Y-m-d', 'after:2000-01-01', 'before:+6 years'];

        return [
            'job_posting_id' => ['nullable', 'integer', Rule::exists('job_postings', 'id')->where('status', 'open')
                ->where(fn ($q) => $q->whereNull('closes_on')->orWhereDate('closes_on', '>=', today()))],
            'role' => ['required', Rule::enum(JobRole::class)],
            'depot' => ['required', Rule::in(Depots::keys())],
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'phone' => ['required', 'string', 'max:30', new UkPhone],
            'email' => [$role?->needsCv() ? 'required' : 'nullable', 'email:rfc', 'max:255'],
            'postcode' => ['nullable', 'string', 'max:10'],
            'experience' => ['required', Rule::in(array_keys(JobApplication::EXPERIENCE))],
            'licence_categories' => [$drives ? 'required' : 'nullable', 'array'],
            'licence_categories.*' => ['string', Rule::in(array_keys((array) ga('careers.licence_categories')))],
            'cpc_expires_on' => $card,
            'tacho_card_expires_on' => $card,
            'licence_points' => [$drives ? 'required' : 'nullable', 'integer', 'min:0', 'max:12'],
            'endorsements' => ['nullable', 'string', 'max:200'],
            'hiab_qualification' => [$role === JobRole::HiabDriver ? 'required' : 'nullable', 'string', 'max:160'],
            'forklift' => ['nullable', 'array'],
            'forklift.*' => ['string', Rule::in(array_keys((array) ga('careers.forklift')))],
            'right_to_work' => ['accepted'],
            'available_from' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:today'],
            'message' => ['nullable', 'string', 'max:2000'],
            'cv' => [$role?->needsCv() ? 'required' : 'nullable', 'file', 'max:5120', 'mimes:'.implode(',', JobApplication::CV_TYPES)],
            'consent' => ['accepted'],
        ];
    }

    /** @return array<int, \Closure> */
    public function after(): array
    {
        return [function (Validator $v): void {
            $cats = (array) $this->input('licence_categories', []);
            if ($this->role()?->drives() && ! in_array('CE', $cats, true) && ! in_array('C', $cats, true)) {
                $v->errors()->add('licence_categories', 'Driving roles need category C or C+E on your licence.');
            }
            if ((int) $this->input('licence_points') > 0 && ! $this->filled('endorsements')) {
                $v->errors()->add('endorsements', 'Please list the endorsement codes for your penalty points, for example SP30.');
            }
        }];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'first_name' => 'first name', 'last_name' => 'last name', 'available_from' => 'start date', 'cv' => 'CV', 'role' => 'role',
            'cpc_expires_on' => 'Driver CPC expiry date', 'tacho_card_expires_on' => 'tacho card expiry date', 'licence_points' => 'penalty points',
            'licence_categories' => 'licence categories', 'hiab_qualification' => 'crane qualification',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'job_posting_id.exists' => 'That vacancy has closed. Choose another, or send a general application.',
            'role.required' => 'Choose the role you are applying for.',
            'depot.required' => 'Choose the depot you would like to work from.',
            'experience.required' => 'Choose how much experience you have.',
            'licence_categories.required' => 'Tick the categories on your driving licence.',
            'cpc_expires_on.required' => 'Enter the expiry date on your Driver CPC card (DQC).',
            'tacho_card_expires_on.required' => 'Enter the expiry date on your digital tachograph driver card.',
            'licence_points.required' => 'Enter your current penalty points (0 if none).',
            'hiab_qualification.required' => 'Tell us your lorry loader (Hiab) qualification, for example the card scheme and expiry.',
            'right_to_work.accepted' => 'You must have the right to work in the UK to apply. We check this before any offer.',
            'cv.required' => 'Transport office roles need a CV (PDF or Word).',
            'email.required' => 'Transport office roles need an email address.',
            'cv.max' => 'Your CV must be 5 MB or smaller.',
            'cv.mimes' => 'Your CV must be a PDF or Word document.',
            'cv.uploaded' => 'Your CV could not be uploaded. It must be 5 MB or smaller.',
            'consent.accepted' => 'Please agree to us keeping your application on file.',
        ];
    }
}
