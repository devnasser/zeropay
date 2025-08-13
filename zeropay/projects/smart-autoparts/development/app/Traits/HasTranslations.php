<?php

namespace App\Traits;

use Illuminate\Support\Facades\App;

trait HasTranslations
{
    /**
     * Get translated attribute
     */
    public function getTranslation(string $key, string $locale = null): ?string
    {
        $locale = $locale ?: App::getLocale();
        $translations = $this->getTranslations($key);
        
        return $translations[$locale] ?? $translations[config('app.fallback_locale')] ?? null;
    }
    
    /**
     * Get all translations for an attribute
     */
    public function getTranslations(string $key): array
    {
        $value = $this->getAttribute($key);
        
        if (is_string($value)) {
            $value = json_decode($value, true);
        }
        
        return is_array($value) ? $value : [];
    }
    
    /**
     * Set translation for specific locale
     */
    public function setTranslation(string $key, string $locale, ?string $value): self
    {
        $translations = $this->getTranslations($key);
        
        if ($value === null || $value === '') {
            unset($translations[$locale]);
        } else {
            $translations[$locale] = $value;
        }
        
        $this->setAttribute($key, $translations);
        
        return $this;
    }
    
    /**
     * Set translations from array
     */
    public function setTranslations(string $key, array $translations): self
    {
        $this->setAttribute($key, $translations);
        
        return $this;
    }
    
    /**
     * Check if translation exists
     */
    public function hasTranslation(string $key, string $locale = null): bool
    {
        $locale = $locale ?: App::getLocale();
        $translations = $this->getTranslations($key);
        
        return isset($translations[$locale]);
    }
    
    /**
     * Get attribute value based on locale
     */
    public function getAttributeValue($key)
    {
        $value = parent::getAttributeValue($key);
        
        if (in_array($key, $this->translatable ?? [])) {
            return $this->getTranslation($key);
        }
        
        return $value;
    }
}