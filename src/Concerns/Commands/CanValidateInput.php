<?php

namespace SolutionForest\FilamentAccessManagement\Concerns\Commands;

use Closure;
use Illuminate\Support\Facades\Validator;

trait CanValidateInput
{
    protected function askRequired(string $question, string $field, ?string $default = null): string
    {
        return $this->validateInput(fn () => $this->ask($question, $default), $field, ['required']);
    }

    /**
     * @param  Closure(): mixed  $callback
     * @param  array<int, string>  $rules
     * @param  Closure(mixed): mixed|null  $onError
     */
    protected function validateInput(Closure $callback, string $field, array $rules, ?Closure $onError = null): string
    {
        $input = $callback();

        $validator = Validator::make(
            [$field => $input],
            [$field => $rules],
        );

        if ($validator->fails()) {
            $this->error(strval($validator->errors()->first()));

            if ($onError) {
                $onError($validator);
            }

            return $this->validateInput($callback, $field, $rules);
        }

        if (is_string($input)) {
            return $input;
        }

        if (is_int($input) || is_float($input)) {
            return (string) $input;
        }

        return '';
    }
}
