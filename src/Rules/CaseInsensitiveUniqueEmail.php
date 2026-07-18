<?php

namespace Aura\Base\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

/**
 * Case-insensitive uniqueness for the users.email column.
 *
 * Laravel's built-in `unique` rule compares with the column's collation, which is
 * case-SENSITIVE on SQLite (and any binary collation). That let two accounts
 * differing only by casing coexist — inconsistent with invitation acceptance,
 * which matches the invited email case-INSENSITIVELY (strcasecmp). This rule
 * closes that gap in validation (no DB collation change, no stored-email
 * normalization): it rejects any address whose lowercased form already exists.
 *
 * On an Edit form the current record must be ignored, exactly like the framework
 * `unique` rule; call ignore() with the model key (the Edit component wires this
 * up automatically, mirroring how it rewrites string `unique:` rules).
 */
class CaseInsensitiveUniqueEmail implements ValidationRule
{
    protected string $idColumn = 'id';

    protected int|string|null $ignoreId = null;

    public function ignore(int|string|null $id, string $idColumn = 'id'): static
    {
        $this->ignoreId = $id;
        $this->idColumn = $idColumn;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Emptiness is the job of the required/email rules — stay silent here.
        if (! is_string($value) || trim($value) === '') {
            return;
        }

        $query = DB::table('users')
            ->whereRaw('lower(email) = ?', [mb_strtolower($value)]);

        if ($this->ignoreId !== null) {
            $query->where($this->idColumn, '!=', $this->ignoreId);
        }

        if ($query->exists()) {
            $fail('validation.unique')->translate();
        }
    }
}
