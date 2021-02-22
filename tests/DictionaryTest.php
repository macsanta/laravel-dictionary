<?php

namespace KamilZawada\LaravelDictionary\Tests;

use Illuminate\Support\Facades\Config;
use KamilZawada\LaravelDictionary\Dictionary;

class DictionaryTest extends TestCase
{
    public $dictionary;
    public $testTranslations;

    public function setUp()
    {
        parent::setUp();

        $this->testTranslations = [
            'en' => [
                'json' => [
                    "label1" => "label1 en",
                    "label2" => "label2 en",
                    "Rejestracja" => "Register",
                    "Słownik" => "Dictionary",
                ],
                'pagination' => [
                    'previous' => '&laquo; Previous',
                    'next' => 'Next &raquo;',
                ],
                'passwords' => [
                    'password' => 'Passwords must be at least six characters and match the confirmation.',
                    'reset' => 'Your password has been reset!',
                    'sent' => 'We have e-mailed your password reset link!',
                ],
            ],
            'pl' => [
                'json' => [
                    "label1" => "label1 pl",
                    "label2" => "label2 pl",
                    "Rejestracja" => "Rejestracja",
                    "Słownik" => "Słownik",
                ],
                'pagination' => [
                    'previous' => '&laquo; Poprzednia',
                    'next' => 'Następna &raquo;',
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

        $this->createTempFiles();       

        $this->withoutExceptionHandling();

        Config::set('dictionary.path', __DIR__.'/temp_lang');
        Config::set('dictionary.viewsPath', __DIR__.'/temp_views');

        $this->app['path.lang'] = Config::get('dictionary.path');
    }

    public function test_it_can_access_dashboard()
    {
        $this->get(route('dictionary.dashboard'))
            ->assertStatus(200);
    }

    public function test_it_can_fetch_translations_from_json_files()
    {
        $dictionary = new Dictionary();

        $plJson = $dictionary->loadTranslations($language='pl', $group='json');

        $this->assertEquals($this->testTranslations['pl']['json'], $plJson);
    }

    public function test_it_can_fetch_translations_from_array_files()
    {
        $dictionary = new Dictionary();

        $plPagination = $dictionary->loadTranslations($language='pl', $group='pagination');

        $this->assertEquals($this->testTranslations['pl']['pagination'], $plPagination); 
    }

    public function test_it_can_load_translations_languages()
    {
        $this->get(route('dictionary.languages'))
            ->assertStatus(200)
            ->assertExactJson(array_keys($this->testTranslations));
    }

    public function test_it_can_load_translations_groups()
    {
        $expected = array_keys($this->testTranslations['en']);
        $expected = array_merge($expected, ['packagename::packagefile']);

        $this->get(route('dictionary.groups', ['language' => 'en']))
            ->assertStatus(200)
            ->assertExactJson($expected);
    }

    public function test_it_can_load_translations_by_language_and_group()
    {
        $this->get(route('dictionary.index', ['language' => 'pl', 'group' => 'json']))
            ->assertStatus(200)
            ->assertExactJson($this->testTranslations['pl']['json']);

        $this->get(route('dictionary.index', ['language' => 'en', 'group' => 'passwords']))
            ->assertStatus(200)
            ->assertExactJson($this->testTranslations['en']['passwords']);

    }

    public function test_it_can_update_json_translation()
    {
        $this->patch(route('dictionary.update', ['language' => 'pl', 'group' => 'json', 'label' => 'label1', 'value' => 'label1 pl updated']))
            ->assertStatus(200);

        $changedJsonPl = $this->testTranslations['pl']['json'];
        $changedJsonPl['label1'] = 'label1 pl updated';

        $this->get(route('dictionary.index', ['language' => 'pl', 'group' => 'json']))
            ->assertStatus(200)
            ->assertExactJson($changedJsonPl);

    }

    public function test_it_can_update_array_translation()
    {
        $this->patch(route('dictionary.update', ['language' => 'en', 'group' => 'passwords', 'label' => 'reset', 'value' => 'Your password has been reset! updated']))
            ->assertStatus(200);

        $changedJsonPl = $this->testTranslations['en']['passwords'];
        $changedJsonPl['reset'] = 'Your password has been reset! updated';

        $this->get(route('dictionary.index', ['language' => 'en', 'group' => 'passwords']))
            ->assertStatus(200)
            ->assertExactJson($changedJsonPl);

    }

    public function test_it_can_add_json_translation()
    {
        $this->post(route('dictionary.store', ['group' => 'json', 'label' => 'new label', 'values' => [
            'pl' => 'new pl value',
            'en' => 'new en value',
        ]]))->assertStatus(200);

        $changedJsonPl = $this->testTranslations['pl']['json'];
        $changedJsonPl['new label'] = 'new pl value';

        $this->get(route('dictionary.index', ['language' => 'pl', 'group' => 'json']))
            ->assertStatus(200)
            ->assertExactJson($changedJsonPl);

        $changedJsonEn = $this->testTranslations['en']['json'];
        $changedJsonEn['new label'] = 'new en value';

        $this->get(route('dictionary.index', ['language' => 'en', 'group' => 'json']))
            ->assertStatus(200)
            ->assertExactJson($changedJsonEn);
    }

    /** @test */
    public function it_can_add_translations_with_empty_values()
    {
        $this->post(route('dictionary.store', ['group' => 'json', 'label' => 'new label', 'values' => [
            'pl' => 'new pl value',
            'en' => 'new en value',
        ]]))->assertStatus(200);

        $changedJsonPl = $this->testTranslations['pl']['json'];
        $changedJsonPl['new label'] = 'new pl value';

        $this->get(route('dictionary.index', ['language' => 'pl', 'group' => 'json']))
            ->assertStatus(200)
            ->assertExactJson($changedJsonPl);

        $changedJsonEn = $this->testTranslations['en']['json'];
        $changedJsonEn['new label'] = 'new en value';

        $this->get(route('dictionary.index', ['language' => 'en', 'group' => 'json']))
            ->assertStatus(200)
            ->assertExactJson($changedJsonEn);
    }


    public function test_it_can_add_array_translation()
    {
        $this->post(route('dictionary.store', ['group' => 'passwords', 'label' => 'new label', 'values' => [
            'pl' => 'new pl value',
            'en' => 'new en value',
        ]]))->assertStatus(200);

        $changedArrayPl = $this->testTranslations['pl']['passwords'];
        $changedArrayPl['new label'] = 'new pl value';

        $this->get(route('dictionary.index', ['language' => 'pl', 'group' => 'passwords']))
            ->assertStatus(200)
            ->assertExactJson($changedArrayPl);

        $changedJsonEn = $this->testTranslations['en']['passwords'];
        $changedJsonEn['new label'] = 'new en value';

        $this->get(route('dictionary.index', ['language' => 'en', 'group' => 'passwords']))
            ->assertStatus(200)
            ->assertExactJson($changedJsonEn);
    }

    public function test_it_can_delete_json_translation()
    {
        $this->delete(route('dictionary.delete', ['group' => 'json', 'label' => 'label1']))
            ->assertStatus(200); 

        $changedJsonPl = $this->testTranslations['pl']['json'];
        unset($changedJsonPl['label1']);

        $changedJsonEn = $this->testTranslations['en']['json'];
        unset($changedJsonEn['label1']);

        $this->get(route('dictionary.index', ['language' => 'pl', 'group' => 'json']))
            ->assertStatus(200)
            ->assertExactJson($changedJsonPl);

        $this->get(route('dictionary.index', ['language' => 'en', 'group' => 'json']))
            ->assertStatus(200)
            ->assertExactJson($changedJsonEn);
    }

    public function test_it_can_delete_array_translation()
    {
        $this->delete(route('dictionary.delete', ['group' => 'passwords', 'label' => 'reset']))
            ->assertStatus(200); 

        $changedArrayPl = $this->testTranslations['pl']['passwords'];
        unset($changedArrayPl['reset']);

        $changedArrayEn = $this->testTranslations['en']['passwords'];
        unset($changedArrayEn['reset']);

        $this->get(route('dictionary.index', ['language' => 'pl', 'group' => 'passwords']))
            ->assertStatus(200)
            ->assertExactJson($changedArrayPl);

        $this->get(route('dictionary.index', ['language' => 'en', 'group' => 'passwords']))
            ->assertStatus(200)
            ->assertExactJson($changedArrayEn);
    }

    /** @test */
    public function it_can_search_in_translations()
    {
        $dictionary = new Dictionary();

        $found = $dictionary->loadTranslations($language='pl', $group='json', $keyword='label');
        $expected = [
            "label1" => "label1 pl",
            "label2" => "label2 pl",
        ];

        $this->assertEquals($expected, $found);

        $found = $dictionary->loadTranslations($language='pl', $group='passwords', $keyword='must be at');
        $expected = [
            'min.numeric' => 'The :attribute must be at least :min.',
            'min.file'    => 'The :attribute must be at least :min kilobytes.',
            'min.string'  => 'The :attribute must be at least :min characters.',
            'password' => 'Hasło musi mieć przynajmniej 6 znaków',
        ];

        $this->assertEquals($expected, $found);
    }

    /** @test */
    public function it_can_search_in_translations_case_insensitive()
    {
        $dictionary = new Dictionary();

        $found = $dictionary->loadTranslations($language='pl', $group='json', $keyword='rejestracja');
        $expected = [
            "Rejestracja" => "Rejestracja",
        ];

        $this->assertEquals($expected, $found);

    }

    /** @test */
    public function it_can_search_in_every_language()
    {
        $dictionary = new Dictionary();

        $found = $dictionary->loadTranslations($language='pl', $group='json', $keyword='label1 en');

        $expected = [
            "label1" => "label1 pl",
        ];

        $this->assertEquals($expected, $found);

    }

    /** @test */
    public function it_can_load_new_translation_labels_from_views()
    {
        $dictionary = new Dictionary();
        $newLabels = $dictionary->getLabelsFromViews($language='pl');
        sort($newLabels);

        $expectedLabels = [
            'new json translation',
            'validation.between.numeric',
            'validation.new.translation1',
            'validation.new.translation2',
            'wartość z pl znakami ąęół .',
            'pagination.new_pagination1',
            'pagination.new_pagination2',
            'packagename::packagefile.newlabel',
        ];
        sort($expectedLabels);

        $this->assertArraySubset($expectedLabels, $newLabels);

        $dictionary->add($dictionary::JSON_LABEL, 'new json translation', ['pl' => 'new json translation']);
        $dictionary->add('validation', 'between.numeric', ['pl' => 'between.numeric']);
        $dictionary->add('validation', 'new.translation1', ['pl' => 'new.translation1']);
        $dictionary->add('validation', 'new.translation2', ['pl' => 'new.translation2']);
        $dictionary->add('pagination', 'new_pagination1', ['pl' => 'new_pagination1']);
        $dictionary->add('pagination', 'new_pagination2', ['pl' => 'new_pagination2']);
        $dictionary->add($dictionary::JSON_LABEL, 'wartość z pl znakami ąęół .', ['pl' => 'wartość z pl znakami ąęół .']);
        $dictionary->add('packagename::packagefile', 'newlabel', ['pl' => 'newlabel value']);

        $newLabels = $dictionary->getLabelsFromViews($language='pl');
        $this->assertCount(0, $newLabels);
    }

    /** @test */
    public function it_can_import_new_translation_labels_from_views()
    {
        $this->post(route('dictionary.importFromViews'))->assertStatus(200);

        $changedJsonPl = $this->testTranslations['pl']['json'];
        $changedJsonPl['new json translation'] = 'new json translation';
        $changedJsonPl['wartość z pl znakami ąęół .'] = 'wartość z pl znakami ąęół .';

        $this->get(route('dictionary.index', ['language' => 'pl', 'group' => 'json']))
            ->assertStatus(200)
            ->assertExactJson($changedJsonPl);

        $changedArrayPl = $this->testTranslations['pl']['pagination'];
        $changedArrayPl['new_pagination1'] = 'new_pagination1';
        $changedArrayPl['new_pagination2'] = 'new_pagination2';

        $this->get(route('dictionary.index', ['language' => 'pl', 'group' => 'pagination']))
            ->assertStatus(200)
            ->assertExactJson($changedArrayPl);

        $changedArrayValidationPl = $this->testTranslations['pl']['validation'] ?? [];
        $changedArrayValidationPl['between.numeric'] = 'between.numeric';
        $changedArrayValidationPl['new.translation1'] = 'new.translation1';
        $changedArrayValidationPl['new.translation2'] = 'new.translation2';

        $this->get(route('dictionary.index', ['language' => 'pl', 'group' => 'validation']))
            ->assertStatus(200)
            ->assertExactJson($changedArrayValidationPl);
    }

    /** @test */
    public function it_can_download_csv_file_with_exported_translation()
    {
        $this->get(route('dictionary.export'))
            ->assertHeader('Content-type', 'text/csv; charset=UTF-8')
            ->assertHeader('Content-Disposition', 'attachment; filename=export.csv')
            ->assertStatus(200);

    }
}
