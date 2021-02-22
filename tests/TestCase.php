<?php

namespace KamilZawada\LaravelDictionary\Tests;

use Illuminate\Support\Facades\File;
use KamilZawada\LaravelDictionary\LaravelDictionaryServiceProvider;


class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app)
    {
        return [LaravelDictionaryServiceProvider::class];
    }

    public function createTempFiles()
    {
        $this->deleteLanguageFiles();
        $this->createJsonFiles();
        $this->createArrayFiles();
        $this->createVendorFiles();
    }

    public function deleteLanguageFiles()
    {
        collect(File::allFiles(__DIR__.'/temp_lang/'))->each(function($file){
            if(in_array($file->getExtension(), ['php', 'json']))
            {
                File::delete($file->getRealPath());
            }
        });
    }

    public function createJsonFiles()
    {
        foreach($this->testTranslations as $lang=>$groups)
        {
            file_put_contents(__DIR__.'/temp_lang/'.$lang.'.json', json_encode($groups['json']));
        }
    }

    private function transformDottedLabelsToArrays($values)
    {
        foreach($values as $label=>$value)
        {
            if(strpos($label, '.')!==false)
            {
                array_set($values, $label, $value);
                unset($values[$label]);
            }
        }

        return $values;
    }

    public function writeSubArray($label, $value)
    {
        $content = '"'.$label.'" => ['.PHP_EOL;
        foreach($value as $sublabel=>$subvalue)
        {
            if(is_array($subvalue))
            {
                $content .= $this->writeSubArray($sublabel, $subvalue);
            }
            else
            {
                $content .= '"'.$sublabel.'" => "'.$subvalue.'",'.PHP_EOL;
            }
        }
        $content .= '],'.PHP_EOL;

        return $content;
    }

    private function createVendorFiles()
    {
        $content = '<?php'.PHP_EOL.PHP_EOL;
        $content .= 'return ['.PHP_EOL;
        $content .= "'packagename' => 'Package name en',".PHP_EOL;
        $content .= "'packagedescription' => 'Package description en',".PHP_EOL;
        $content .= "'packageparentlabel' => [".PHP_EOL;
        $content .= "'sublabel1' => 'sublabel value 1 en',".PHP_EOL;
        $content .= "'sublabel2' => 'sublabel value 2 en',".PHP_EOL;
        $content .= "]".PHP_EOL;
        $content .= '];'.PHP_EOL;

        file_put_contents(__DIR__.'/temp_lang/vendor/packagename/en/packagefile.php', $content);

        $content = '<?php'.PHP_EOL.PHP_EOL;
        $content .= 'return ['.PHP_EOL;
        $content .= "'packagename' => 'Package name pl',".PHP_EOL;
        $content .= "'packagedescription' => 'Package description pl',".PHP_EOL;
        $content .= "'packageparentlabel' => [".PHP_EOL;
        $content .= "'sublabel1' => 'sublabel value 1 pl',".PHP_EOL;
        $content .= "'sublabel2' => 'sublabel value 2 pl',".PHP_EOL;
        $content .= "]".PHP_EOL;
        $content .= '];'.PHP_EOL;

        file_put_contents(__DIR__.'/temp_lang/vendor/packagename/pl/packagefile.php', $content);
    }

    public function createArrayFiles()
    {
        foreach($this->testTranslations as $lang=>$groups)
        {
            foreach($groups as $groupName=>$values)
            {
                if($groupName!='json')
                {
                    $values = $this->transformDottedLabelsToArrays($values);

                    $content = '<?php'.PHP_EOL.PHP_EOL;
                    $content .= 'return ['.PHP_EOL;
                    foreach($values as $label=>$value)
                    {
                        if(is_array($value))
                        {
                            $content .= $this->writeSubArray($label, $value);
                        }
                        else
                        {
                            $content .= '"'.$label.'" => "'.$value.'",'.PHP_EOL;
                        }
                    }
                    $content .= '];'.PHP_EOL;

                    file_put_contents(__DIR__.'/temp_lang/'.$lang.'/'.$groupName.'.php', $content);

                }
            }
        }
    }


}