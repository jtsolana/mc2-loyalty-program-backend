<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

trait HashTrait
{
    public function resolveRouteBinding($value, $field = null): ?Model
    {
        $decodedValue = hashids_decode($value);

        return $this->where($field ?? $this->getRouteKeyName(), $decodedValue)->first();
    }

    protected function hashedId(): Attribute
    {
        return Attribute::make(
            get: fn () => hashids_encode($this->{$this->getRouteKeyName()}),
        );
    }

    public static function findByHash(string $value): ?static
    {
        $decodedValue = hashids_decode($value);

        return static::find($decodedValue);
    }
}
