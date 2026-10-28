<?php

namespace App\Policies;

use App\Enums\Area;
use App\Models\ApplicantDocument;
use App\Models\JobApplication;
use App\Models\User;

/** HR and administrators. Applications, CVs and card copies are personal data. */
class JobApplicationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->works(Area::Recruitment);
    }

    public function view(User $user, JobApplication $application): bool
    {
        return $user->works(Area::Recruitment);
    }

    public function update(User $user, JobApplication $application): bool
    {
        return $user->works(Area::Recruitment);
    }

    public function openCv(User $user, JobApplication $application): bool
    {
        return $application->cv_path !== null && $this->view($user, $application);
    }

    /** Only a document of this application. */
    public function openDocument(User $user, JobApplication $application, ApplicantDocument $document): bool
    {
        return $document->job_application_id === $application->id && $this->view($user, $application);
    }

    /** Hiring creates a staff compliance record, so it needs the compliance area too. */
    public function hire(User $user, JobApplication $application): bool
    {
        return $this->update($user, $application) && $user->works(Area::Compliance);
    }
}
