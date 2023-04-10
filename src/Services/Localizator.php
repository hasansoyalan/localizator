<?php

namespace Amirami\Localizator\Services;

use Amirami\Localizator\Contracts\Collectable;
use Amirami\Localizator\Contracts\Translatable;
use Amirami\Localizator\Contracts\Writable;

class Localizator
{
    /**
     * @param Translatable $keys
     * @param string $type
     * @param string $locale
     * @return void
     */
    public function localize(Translatable $keys, string $type, string $locale, bool $removeMissing): void
    {
        $this->getWriter($type)->put($locale, $this->collect($keys, $type, $locale, $removeMissing));
    }

    /**
     * @param Translatable $keys
     * @param string $type
     * @param string $locale
     * @return Translatable
     */
    protected function collect(Translatable $keys, string $type, string $locale, bool $removeMissing): Translatable
    {
        $translated = $this->getCollector($type)->getTranslated($locale)
            ->when($removeMissing, function (Translatable $keyCollection) use ($keys) {
                return $keyCollection->intersectByKeys($keys);
            });

        $newTranslates = $keys->diffKeys($translated);

        return $keys
            ->merge($translated)
            ->when(config('localizator.sort'), function (Translatable $keyCollection) {
                    return $keyCollection->sortAlphabetically();
                })
            ->mapWithKeys(function ($value, $key) use ($newTranslates) {
                return [$newTranslates->has($key) ? "_".$key : $key =>  $value];
            })->sortBy(fn($value, $key) => $key[0] === "_");

    }

    /**
     * @param string $type
     * @return Writable
     */
    protected function getWriter(string $type): Writable
    {
        return app("localizator.writers.$type");
    }

    /**
     * @param string $type
     * @return Collectable
     */
    protected function getCollector(string $type): Collectable
    {
        return app("localizator.collector.$type");
    }
}
