<?php

namespace App\Policies;

use App\Enums\Area;
use App\Models\Quote;
use App\Models\QuoteFile;
use App\Models\User;

/** Traffic and administrators. */
class QuotePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->works(Area::Quotes);
    }

    public function view(User $user, Quote $quote): bool
    {
        return $user->works(Area::Quotes);
    }

    public function update(User $user, Quote $quote): bool
    {
        return $user->works(Area::Quotes);
    }

    /** Attachments: only files that belong to this request. */
    public function openFile(User $user, Quote $quote, QuoteFile $file): bool
    {
        return $file->quote_id === $quote->id && $this->view($user, $quote);
    }
}
