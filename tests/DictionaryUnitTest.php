<?php

namespace KamilZawada\LaravelDictionary\Tests;

use KamilZawada\LaravelDictionary\Dictionary;
use Illuminate\Support\Facades\Config;

class DictionaryUnitTest extends TestCase
{

    public $dictionary;
    public $testTranslations;

    public function setUp()
    {
        parent::setUp();

        $this->testTranslations = [
            'en' => [
                'json' => [
                    'label1' => 'label1 en',
                    'label2' => 'label2 en',
                ],
                'pagination' => [
                    'previous' => 'Previous',
                    'next' => 'Next',
                ],
                'passwords' => [
                    'password' => 'Passwords must be at least six characters and match the confirmation.',
                    'reset' => 'Your password has been reset!',
                    'sent' => 'We have e-mailed your password reset link!',
                ],
            ],
            'pl' => [
                'json' => [
                    'label1' => 'label1 pl',
                    'label2' => 'label2 pl',
                    'label3' => 'label3 pl',
                ],
                'pagination' => [
                    'previous' => 'Poprzednia',
                    'next' => 'Następna',
                ],
                'passwords' => [
                    'password' => 'Hasło musi mieć przynajmniej 6 znaków',
                    'reset' => 'Twoje hasło zostało zmienione!',
                    'max.numeric' => 'The :attribute may not be greater than :max.',
                    'max.file'    => 'The :attribute may not be greater than :max kilobytes.',
                    'max.string'  => 'The :attribute may not be greater than :max characters.',
                    'max.array'   => 'The :attribute may not have more than :max items.',
                    'sent' => 'Link do zmiany hasła został wysłany!',
                    'min.numeric' => 'The :attribute must be at least :min.',
                    'min.file'    => 'The :attribute must be at least :min kilobytes.',
                    'min.string'  => 'The :attribute must be at least :min characters.',
                    'min.array'   => 'The :attribute must have at least :min items.',
                    'custom.attribute-name.rule-name' => 'custom-message',
                ],
            ],
        ];

        Config::set('dictionary.path', __DIR__.'/temp_lang');

        $this->app['path.lang'] = Config::get('dictionary.path');

        $this->dictionary = new Dictionary();
        $this->dictionary->setTranslations($this->testTranslations);
    }

    public function test_it_can_load_translations()
    {
        $plJson = $this->dictionary->loadTranslations($language='pl', $group='json');
        $enPagination = $this->dictionary->loadTranslations($language='en', $group='pagination');

        $this->assertEquals($this->testTranslations['pl']['json'], $plJson);
        $this->assertEquals($this->testTranslations['en']['pagination'], $enPagination);

    }

    public function test_it_can_load_translations_languages()
    {
        $languages = $this->dictionary->loadLanguages();

        $this->assertEquals(array_keys($this->testTranslations), $languages);

    }

    public function test_it_can_load_translations_groups()
    {
        $groupsPl = $this->dictionary->loadGroups($language='pl');  

        $this->assertEquals(array_keys($this->testTranslations['pl']), $groupsPl);     

    }

    public function test_it_can_update_translation()
    {
        $this->dictionary->update($language='pl', $group='json', $label='label1', $newValue='label1 pl updated');

        $pl = $this->dictionary->loadTranslations($language='pl', $group='json');

        $this->assertEquals('label1 pl updated', $pl['label1']);

    }

    public function test_it_can_add_translation()
    {
        $this->dictionary->add($group='json', $label='newLabel', $newValue=['pl' => 'new value']);

        $pl = $this->dictionary->loadTranslations($language='pl', $group='json');

        $this->assertEquals('new value', $pl['newLabel']);
    }

    public function test_nested_groups_are_transformed_to_labels_when_fetched()
    {
        $plPasswords = $this->dictionary->loadTranslations($language='pl', $group='passwords');

        $this->assertEquals($this->testTranslations['pl']['passwords'], $plPasswords);
    }

    public function test_it_can_delete_translation()
    {
        $this->dictionary->delete($group='json', $label='label2');

        $pl = $this->dictionary->loadTranslations($language='pl', $group='json');

        $this->assertFalse(array_key_exists('label2', $pl));

    }

    /** @test */
    public function it_can_prepare_array_for_export()
    {
        $translations = [
            'en' => [
                'json' => [
                    'label1' => 'label1 en',
                    'label2' => 'label2 en',
                ],
                'pagination' => [
                    'previous' => 'Previous',
                ],
            ],
            'pl' => [
                'json' => [
                    'label1' => 'label1 pl',
                    'label2' => 'label2 pl',
                    'label3' => 'label3 pl',
                ],
            ]
        ];

        $this->dictionary->setTranslations($translations);

        $forExport = $this->dictionary->loadArrayForExport();

        $this->assertEquals([
            0 => ['', 'en', 'pl'],
            'json.label1' =>[0 => 'json.label1', 'en' => 'label1 en', 'pl' => 'label1 pl'],
            'json.label2' => [0 => 'json.label2', 'en' => 'label2 en', 'pl' => 'label2 pl'],
            'json.label3' => [0 => 'json.label3', 'en' => '', 'pl' => 'label3 pl'],
            'pagination.previous' => [0 => 'pagination.previous', 'en' => 'Previous', 'pl' => ''],

        ], $forExport);

        $translations = [
            'en' => [
                'json' => [
                    'label1' => 'label1 en',
                    'label2' => 'label2 en',
                ],
                'pagination' => [
                    'previous' => 'Previous',
                ],
            ],
            'pl' => [
                'json' => [
                    'label1' => 'label1 pl',
                    'label2' => 'label2 pl',
                    'label3' => 'label3 pl',
                ],
                'passwords' => [
                    'password' => 'Hasło musi mieć przynajmniej 6 znaków',
                    'reset' => 'Twoje hasło zostało zmienione!',
                ],
            ]
        ];

        $this->dictionary->setTranslations($translations);

        $forExport = $this->dictionary->loadArrayForExport();

        $this->assertEquals([
            0 => ['', 'en', 'pl'],
            'json.label1' =>[0 => 'json.label1', 'en' => 'label1 en', 'pl' => 'label1 pl'],
            'json.label2' => [0 => 'json.label2', 'en' => 'label2 en', 'pl' => 'label2 pl'],
            'json.label3' => [0 => 'json.label3', 'en' => '', 'pl' => 'label3 pl'],
            'pagination.previous' => [0 => 'pagination.previous', 'en' => 'Previous', 'pl' => ''],
            'passwords.password' => [0 => 'passwords.password', 'en' => '', 'pl' => 'Hasło musi mieć przynajmniej 6 znaków'],
            'passwords.reset' => [0 => 'passwords.reset', 'en' => '', 'pl' => 'Twoje hasło zostało zmienione!'],

        ], $forExport);

    }

    /** @test */
    public function it_can_import_csv()
    {
        $csv = [
            ['', 'en', 'pl'],
            ['json.label1', 'label1 en edited', 'label1 pl edited'],
            ['json.label2', 'label2 en edited', 'label2 pl edited'],
            ['pagination.previous', 'Previous edited', 'Poprzednia edited'],
            ['pagination.next', 'Next edited', 'Następna edited'],
            ['passwords.custom.attribute-name.rule-name', 'custom-message en edited', 'custom-message pl edited'],
        ];

        $this->dictionary->import($csv);

        $translations = $this->dictionary->getTranslations();

        $this->assertEquals($csv[1][1], $translations['en']['json']['label1']);
        $this->assertEquals($csv[1][2], $translations['pl']['json']['label1']);

        $this->assertEquals($csv[2][1], $translations['en']['json']['label2']);
        $this->assertEquals($csv[2][2], $translations['pl']['json']['label2']);

        $this->assertEquals($csv[3][1], $translations['en']['pagination']['previous']);
        $this->assertEquals($csv[3][2], $translations['pl']['pagination']['previous']);

        $this->assertEquals($csv[4][1], $translations['en']['pagination']['next']);
        $this->assertEquals($csv[4][2], $translations['pl']['pagination']['next']);

        $this->assertEquals($csv[5][1], $translations['en']['passwords']['custom.attribute-name.rule-name']);
        $this->assertEquals($csv[5][2], $translations['pl']['passwords']['custom.attribute-name.rule-name']);

    }

}
