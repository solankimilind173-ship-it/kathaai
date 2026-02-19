<?php

namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Seeds supported languages (international and Indian).
     */
    public function run(): void
    {
        $languages = [

            // 🌍 International Core
            ['name' => 'English', 'code' => 'en'],
            ['name' => 'Spanish', 'code' => 'es'],
            ['name' => 'French', 'code' => 'fr'],
            ['name' => 'German', 'code' => 'de'],
            ['name' => 'Portuguese', 'code' => 'pt'],
            ['name' => 'Russian', 'code' => 'ru'],
            ['name' => 'Chinese (Simplified)', 'code' => 'zh'],
            ['name' => 'Chinese (Traditional)', 'code' => 'zh-TW'],
            ['name' => 'Japanese', 'code' => 'ja'],
            ['name' => 'Korean', 'code' => 'ko'],
            ['name' => 'Arabic', 'code' => 'ar'],
            ['name' => 'Turkish', 'code' => 'tr'],
            ['name' => 'Italian', 'code' => 'it'],
            ['name' => 'Dutch', 'code' => 'nl'],
            ['name' => 'Polish', 'code' => 'pl'],
            ['name' => 'Ukrainian', 'code' => 'uk'],
            ['name' => 'Vietnamese', 'code' => 'vi'],
            ['name' => 'Thai', 'code' => 'th'],
            ['name' => 'Indonesian', 'code' => 'id'],

            // 🇮🇳 Indian Languages (Official + Widely Spoken)
            ['name' => 'Hindi', 'code' => 'hi'],
            ['name' => 'Hinglish', 'code' => 'hi-en'], // Custom for AI
            ['name' => 'Bengali', 'code' => 'bn'],
            ['name' => 'Telugu', 'code' => 'te'],
            ['name' => 'Marathi', 'code' => 'mr'],
            ['name' => 'Tamil', 'code' => 'ta'],
            ['name' => 'Gujarati', 'code' => 'gu'],
            ['name' => 'Kannada', 'code' => 'kn'],
            ['name' => 'Malayalam', 'code' => 'ml'],
            ['name' => 'Punjabi', 'code' => 'pa'],
            ['name' => 'Odia', 'code' => 'or'],
            ['name' => 'Assamese', 'code' => 'as'],
            ['name' => 'Urdu', 'code' => 'ur'],
            ['name' => 'Sanskrit', 'code' => 'sa'],
            ['name' => 'Konkani', 'code' => 'kok'],
            ['name' => 'Manipuri', 'code' => 'mni'],
            ['name' => 'Maithili', 'code' => 'mai'],
            ['name' => 'Dogri', 'code' => 'doi'],
            ['name' => 'Bodo', 'code' => 'brx'],
            ['name' => 'Santali', 'code' => 'sat'],
            ['name' => 'Kashmiri', 'code' => 'ks'],
            ['name' => 'Sindhi', 'code' => 'sd'],
        ];

        foreach ($languages as $language) {
            Language::updateOrCreate(
                ['code' => $language['code']],
                $language
            );
        }
    }
}
