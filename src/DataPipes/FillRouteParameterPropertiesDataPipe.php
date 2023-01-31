<?php

namespace Spatie\LaravelData\DataPipes;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Attributes\FromRouteParameter;
use Spatie\LaravelData\Support\DataClass;

class FillRouteParameterPropertiesDataPipe implements DataPipe
{
    public function handle(mixed $payload, DataClass $class, Collection $properties): Collection
    {
        if (! $payload instanceof Request) {
            return $properties;
        }

        foreach ($class->properties as $dataProperty) {
            if (! ($attribute = $dataProperty->attributes->first(fn ($attribute) => $attribute instanceof FromRouteParameter))) {
                continue;
            }

            if (! $attribute->replaceWhenPresentInBody && $properties->has($dataProperty->name)) {
                continue;
            }

            if (($parameter = $payload->route($attribute->routeParameter))) {
                if ($attribute->property === false || (! $attribute->property && is_scalar($parameter))) {
                    $value = $parameter;
                } else {
                    $value = data_get($parameter, $attribute->property ?? $dataProperty->name);
                }

                $properties->put($dataProperty->name, $value);
            }
        }

        return $properties;
    }
}