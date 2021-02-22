<?php


namespace KamilZawada\LaravelDictionary;

use Illuminate\Support\Facades\File;


class FilesManager
{
    private $path;
    private $viewsPath;

    public function __construct($path, $viewsPath)
    {
        $this->path = $path;
        $this->viewsPath = $viewsPath;
    }

    public function fetchFiles()
    {

        $result = $this->fetchJsonFiles();
        foreach($this->fetchArrayFiles() as $language => $group)
        {
            if(!array_key_exists($language, $result))
            {
                $result[$language] = [];
            }

            $result[$language] = array_merge($result[$language], $group);
        }

        foreach($this->fetchVendorFiles() as $language => $group)
        {
            if(!array_key_exists($language, $result))
            {
                //do not add language from package translations
                continue;
            }

            $result[$language] = array_merge($result[$language], $group);
        }


        $result = $this->transformArraysToDottedLabels($result);

        return $result;

    }

    private function fetchJsonFiles()
    {
        return collect(File::allFiles($this->path))->filter(function ($file) {
            return ($file->getExtension() == Dictionary::JSON_LABEL && (strpos($file->getRelativePath(), 'vendor')===false));
        })->mapWithKeys(function($file)  {
            $language = str_replace('.json', '', $file->getFilename());
            return [ $language => [ Dictionary::JSON_LABEL => json_decode(file_get_contents($file->getRealPath()), true) ] ];
        })->toArray();
    }

    private function fetchArrayFiles()
    {
        return collect(File::allFiles($this->path))->filter(function ($file) {
            return ($file->getExtension() == 'php' && (strpos($file->getRelativePath(), 'vendor')===false));
        })->mapToGroups(function($file) {
            $language = $file->getRelativePath();
            $group = str_replace('.php', '', $file->getFilename());
            return [ $language => [ $group => (array) include $file->getPathname() ] ];
        })->map(function($value){
            return collect($value)->flatMap(function($value){
                return $value;
            })->toArray();
        })->toArray();
    }

    private function fetchVendorFiles()
    {
        return collect(File::allFiles($this->path))->filter(function ($file) {
            return ($file->getExtension() == 'php' && (strpos($file->getRelativePath(), 'vendor')!==false));
        })->mapToGroups(function($file) {
            $relativePathArray = explode('/', $file->getRelativePath());
            $group = $relativePathArray[1].'::'.str_replace('.php', '', $file->getFilename());
            $language = array_pop($relativePathArray);
            return [ $language => [ $group => (array) include $file->getPathname() ] ];
        })->map(function($value){
            return collect($value)->flatMap(function($value){
                return $value;
            })->toArray();
        })->toArray();
    }

    public function saveFiles($group=false, $translations = [])
    {
        if(($group==Dictionary::JSON_LABEL)||(!$group))
        {
            $this->saveJsonFiles($translations);
        }

        if(($group!=Dictionary::JSON_LABEL)||(!$group))
        {
            $this->saveArrayFiles($translations);
        }
    }

    private function saveArrayFiles($translations = [])
    {
        $translations = $this->transformDottedLabelsToArrays($translations);

        foreach($translations as $language=>$groups)
        {
            foreach($groups as $groupName=>$values)
            {
                if($groupName!=Dictionary::JSON_LABEL)
                {
                    $content = '<?php'.PHP_EOL.PHP_EOL;
                    $content .= 'return ['.PHP_EOL;
                    foreach($values as $label=>$value)
                    {
                        if(is_array($value))
                        {
                            $content .= $this->getSubArrayAsContent($label, $value);
                        }
                        else
                        {
                            $content .= '"'.$label.'" => "'.$value.'",'.PHP_EOL;
                        }
                    }
                    $content .= '];'.PHP_EOL;

                    if(strpos($groupName, '::')!==false) //package
                    {
                        $path = $this->path.'/vendor/'.explode('::', $groupName)[0].'/'.$language.'/'.explode('::', $groupName)[1].'.php';
                    }
                    else
                    {
                        $path = $this->path.'/'.$language.'/'.$groupName.'.php';
                    }

                    file_put_contents($path, $content);
                }
            }
        }
    }

    private function saveJsonFiles($translations = [])
    {
        foreach($translations as $language=>$groups)
        {
            file_put_contents($this->path.'/'.$language.'.json', json_encode($groups[Dictionary::JSON_LABEL], JSON_PRETTY_PRINT));
        }
    }

    private function getSubArrayAsContent($label, $value)
    {
        $content = '"'.$label.'" => ['.PHP_EOL;
        foreach($value as $sublabel=>$subvalue)
        {
            if(is_array($subvalue))
            {
                $content .= $this->getSubArrayAsContent($sublabel, $subvalue);
            }
            else
            {
                $content .= '"'.$sublabel.'" => "'.$subvalue.'",'.PHP_EOL;
            }
        }
        $content .= '],'.PHP_EOL;

        return $content;
    }

    private function transformArraysToDottedLabels($translations = [])
    {
        foreach($translations as $language=>$groups)
        {
            foreach($groups as $groupName=>$values)
            {
                $translations[$language][$groupName] = $this->transformArrayToDottedLabels($values, $narr = array(), $nkey = '');
            }
        }

        return $translations;
    }

    private function transformArrayToDottedLabels($arr, $narr = array(), $nkey = '')
    {
        foreach ($arr as $key => $value) {
            if (is_array($value)) {
                $narr = array_merge($narr, $this->transformArrayToDottedLabels($value, $narr, $nkey . $key . '.'));
            } else {
                $narr[$nkey . $key] = $value;
            }
        }

        return $narr;
    }

    private function transformDottedLabelsToArrays($translations = [])
    {
        foreach($translations as $language=>$groups)
        {
            foreach($groups as $groupName=>$values)
            {
                foreach($values as $label=>$value)
                {
                    if(strpos($label, '.')!==false)
                    {
                        array_set($translations[$language][$groupName], $label, $value);
                        unset($translations[$language][$groupName][$label]);
                    }
                }
            }
        }

        return $translations;
    }

    public function findTranslationsInView()
    {
        /*
         * @copyrights themsaid/laravel-langman-gui
         *
         * https://github.com/themsaid/laravel-langman-gui/blob/master/src/Manager.php
         */
        $functions = ['__', 'trans', 'trans_choice', 'Lang::get', 'Lang::choice', 'Lang::trans', 'Lang::transChoice', '@lang', '@choice'];
        $pattern =
            // See https://regex101.com/r/jS5fX0/5
            '[^\w]'. // Must not start with any alphanum or _
            '(?<!->)'. // Must not start with ->
            '('.implode('|', $functions).')'.// Must start with one of the functions
            "\(".// Match opening parentheses
            "[\'\"]".// Match " or '
            '('.// Start a new group to match:
            '.+'.// Must start with group
            ')'.// Close group
            "[\'\"]".// Closing quote
            "[\),]"  // Close parentheses or new parameter
        ;

        $allTranslations = [];
        collect(File::allFiles($this->viewsPath))->each(function ($file) use ($pattern, &$allTranslations) {
            if (preg_match_all("/$pattern/siU", $file->getContents(), $matches)) {
                $allTranslations[$file->getRelativePathname()] = $matches[2];
            }
        });

        return $allTranslations;
    }


}