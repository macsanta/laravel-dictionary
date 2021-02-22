<?php

namespace KamilZawada\LaravelDictionary;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;

class Dictionary
{
    const JSON_LABEL = 'json';

    /**
     * Translations array
     *
     * @var translations
     */
    private $translations;


    /**
     * FilesManager instance
     *
     * @var filesManager
     */
    private $filesManager;

    /**
     * Dictionary constructor.
     */
    public function __construct()
    {
        $this->filesManager = new FilesManager(Config::get('dictionary.path', App::langPath()), Config::get('dictionary.viewsPath'));

        $this->translations = $this->filesManager->fetchFiles();
    }

    /**
     * Overrides translations array, for test mostly
     *
     * @param array $translations
     */
    public function setTranslations($translations = [])
    {
        $this->translations = $translations;
    }

    /**
     * @return array|translations
     */
    public function getTranslations()
    {
        return $this->translations;
    }

    /**
     * Translation array in given language and group, optional search by keyword
     *
     * @param $language
     * @param $group
     * @param null $keyword
     * @return array
     */
    public function loadTranslations($language, $group, $keyword=null)
    {
        if(!isset($this->translations[$language][$group]))
        {
            return [];
        }

        if(!$keyword)
        {
            return $this->translations[$language][$group];
        }

        $keyword = strtolower($keyword);

        return array_filter(
            $this->translations[$language][$group],
            function($value, $key) use ($keyword, $group)
            {
                return $this->search($group, $key, $keyword);
            },
            ARRAY_FILTER_USE_BOTH);
    }

    /**
     * @param $group
     * @param $label
     * @param $keyword
     * @return bool
     */
    private function search($group, $label, $keyword)
    {
        foreach($this->loadLanguages() as $language)
        {
            if(isset($this->translations[$language][$group][$label])&&((strpos(strtolower($this->translations[$language][$group][$label]), $keyword)!==false) || (strpos(strtolower($label), $keyword)!==false)))
            {
                return true;
            }
        }

        return false;
    }

    /**
     * Array of languges from files
     * ['pl', 'en']
     *
     * @return array
     */
    public function loadLanguages()
    {
        return is_array($this->translations) ? array_keys($this->translations) : [];
    }

    /**
     * Array of groups (filenames)
     * ['json', 'pagination']
     *
     * @param null $language
     * @return array
     */
    public function loadGroups($language=null)
    {
        if(!$language)
            return [];

        return is_array($this->translations) ? array_keys($this->translations[$language]) : [];
    }

    /**
     * Update single translation and save file
     *
     * @param $language
     * @param $group
     * @param $label
     * @param $newValue
     * @return bool
     */
    public function update($language, $group, $label, $newValue)
    {
        $this->translations[$language][$group][$label] = $newValue;

        $this->filesManager->saveFiles($group, $this->translations);
    }

    /**
     * Add single translation in given languages and save file
     *
     * @param $group
     * @param $label
     * @param $newValue
     */
    public function add($group, $label, $newValue)
    {
        if(is_array($newValue))
        {
            foreach($newValue as $language=>$value)
            {
                $this->translations[$language][$group][$label] = $value;
            }
            $this->filesManager->saveFiles($group, $this->translations);
        }
    }

    /**
     * Delete translation and save file
     *
     * @param $language
     * @param $group
     * @param $label
     */
    public function delete($group, $label)
    {
        foreach($this->translations as $lang=>$values)
        {
            if(array_key_exists($label, $values[$group]))
            {
                unset($this->translations[$lang][$group][$label]);
            }
        }

        $this->filesManager->saveFiles($group, $this->translations);
    }

    /**
     * Find new translations in views, import them to array and save files
     */
    public function importLabelsFormViews()
    {
        foreach($this->translations as $language => $group)
        {
            $newLabels = $this->getLabelsFromViews($language);
            foreach($newLabels as $label)
            {
                if((strpos($label, '.')!==false)&&(strpos($label, ' ')===false))
                {
                    $value = explode('.', $label);
                    $groupName = array_shift($value);
                    if(!array_key_exists($groupName, $this->translations[$language]))
                    {
                        $this->translations[$language][$groupName] = [];
                    }
                    array_set($this->translations[$language], $label, implode('.', $value));
                }
                else
                {
                    $this->translations[$language][$this::JSON_LABEL][$label] = $label;
                }
            }
        }

        $this->filesManager->saveFiles(false, $this->translations);
    }

    /**
     * Load new translations from view files in given language
     *
     * @param $language
     * @return array
     */
    public function getLabelsFromViews($language)
    {
        $viewLabels = $this->filesManager->findTranslationsInView();

        if(!sizeof($viewLabels)>0)
        {
            return [];
        }

        $flatLabels = [];

        foreach($this->translations[$language] as $groupName => $group)
        {
            foreach($group as $label => $value)
            {
                $flatLabels[] = (($groupName!=$this::JSON_LABEL)?($groupName.'.'):'').$label;
            }
        }

        $newLabels = [];

        foreach($viewLabels as $file => $labels)
        {
            $newLabels = array_merge($newLabels, collect($labels)->filter(function($label) use ($flatLabels) {
                return !(in_array($label, $flatLabels));
            })->toArray());
        }
        return array_unique($newLabels);
    }

    /**
     * @return mixed
     */
    public function loadArrayForExport()
    {
        $translations = collect($this->getTranslations());

        $dotted = $translations->mapWithKeys(function($group, $language){
            return [$language => array_dot($group)];
        });

        $languages = $this->loadLanguages();

        $result[0] = [
            0 => ''
        ];
        $result[0] = array_merge($result[0], $languages);

        foreach($dotted as $language => $values)
        {
            foreach($values as $label => $value)
            {
                $result[$label][0] = $label;
                foreach($languages as $dictionaryLanguage)
                {
                    $result[$label][$dictionaryLanguage] = '';
                }
            }
        }

        foreach($dotted as $language => $values)
        {
            foreach($values as $label => $value)
            {
                $result[$label][$language] = $value;
            }
        }

        return $result;
    }

    public function import($data)
    {
        $languages = $this->loadLanguages();

        $header = array_shift($data);
        array_shift($header);

        foreach($header as $language)
        {
            if(!in_array($language, $languages))
            {
                return false;
            }
        }

        foreach($data as $row)
        {
            $temp = explode('.', $row[0]);
            $group = array_shift($temp);
            $label = implode('.', $temp);

            foreach($row as $key=>$column)
            {
                if($key==0)
                {
                    continue;
                }

                $this->translations[$header[$key-1]][$group][$label] = $column;

            }
        }

        $this->filesManager->saveFiles(false, $this->translations);

    }
}