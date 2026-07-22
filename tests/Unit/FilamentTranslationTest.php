<?php

namespace Tests\Unit;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Tests\TestCase;

class FilamentTranslationTest extends TestCase
{
    public function test_all_literal_admin_translation_keys_exist_in_both_locales(): void
    {
        $english = $this->dictionary('en');
        $arabic = $this->dictionary('ar');
        $keys = $this->literalTranslationKeys();

        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $english, "Missing English translation: {$key}");
            $this->assertArrayHasKey($key, $arabic, "Missing Arabic translation: {$key}");
        }
    }

    public function test_locale_values_do_not_mix_latin_and_arabic_scripts(): void
    {
        foreach ($this->dictionary('ar') as $key => $value) {
            $withoutPlaceholders = preg_replace('/:[A-Za-z_]+/', '', $value);
            $this->assertDoesNotMatchRegularExpression('/[A-Za-z]/', $withoutPlaceholders, "Latin text in Arabic translation: {$key}");
        }

        foreach ($this->dictionary('en') as $key => $value) {
            $this->assertDoesNotMatchRegularExpression('/[\x{0600}-\x{06FF}]/u', $value, "Arabic text in English translation: {$key}");
        }
    }

    /**
     * @return array<string, string>
     */
    private function dictionary(string $locale): array
    {
        return json_decode(
            file_get_contents(base_path("lang/{$locale}.json")),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
    }

    /**
     * @return array<int, string>
     */
    private function literalTranslationKeys(): array
    {
        $keys = ['Success', 'Information'];
        $directories = [
            app_path('Filament'),
            app_path('Providers/Filament'),
            app_path('Services'),
            app_path('Support'),
            resource_path('views/filament'),
        ];

        foreach ($directories as $directory) {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

            /** @var SplFileInfo $file */
            foreach ($files as $file) {
                if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.php')) {
                    continue;
                }

                $contents = file_get_contents($file->getPathname());
                preg_match_all('/__\(\s*([\'\"])(.*?)\1/s', $contents, $helperMatches);
                preg_match_all('/[\'\"](kpi\.[^\'\"]+)[\'\"]/', $contents, $kpiMatches);
                $keys = [...$keys, ...$helperMatches[2], ...$kpiMatches[1]];
            }
        }

        return array_values(array_unique($keys));
    }
}
