<?php

namespace KamilZawada\LaravelDictionary;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Response;

class DictionaryController extends BaseController
{
    private $dictionary;

    public function __construct(Dictionary $dictionary)
    {
        $this->dictionary = $dictionary;
    }

    public function dashboard()
    {
        return view('dictionary::dashboard');
    }

    public function languages()
    {
        return $this->dictionary->loadLanguages();
    }

    public function groups($language=null)
    {
        return $this->dictionary->loadGroups($language);
    }

    public function index($language, $group=null, $keyword=null)
    {
        return $this->dictionary->loadTranslations($language, $group, $keyword);
    }

    public function update()
    {
        $validated = request()->validate([
            'language' => 'required',
            'group' => 'required',
            'label' => 'required',
            'value' => 'nullable',
        ]);

        $this->dictionary->update($validated['language'], $validated['group'], $validated['label'], $validated['value']);
    }

    public function store()
    {
        $validated = request()->validate([
            'group' => 'required',
            'label' => 'required',
            'values' => 'array',
        ]);

        $this->dictionary->add($validated['group'], $validated['label'], $validated['values']);
    }

    public function destroy()
    {
        $validated = request()->validate([
            'group' => 'required',
            'label' => 'required',
        ]); 

        $this->dictionary->delete($validated['group'], $validated['label']);
    }

    public function importFromViews()
    {
        $this->dictionary->importLabelsFormViews();
    }

    public function export()
    {
        $exportArray = $this->dictionary->loadArrayForExport();

        $headers = [
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Content-type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename=export.csv',
            'Expires'             => '0',
            'Pragma'              => 'public',
        ];

        $callback = function() use ($exportArray)
        {
            $file = fopen('php://output', 'w');
            foreach ($exportArray as $row) {
                fputcsv($file, $row, ';');
            }
            fclose($file);
        };

        return Response::stream($callback, 200, $headers);

    }

    public function import()
    {
        request()->validate([
            'file' => 'required|file|mimetypes:text/csv,text/plain',
        ]);

        $upload=request()->file('file');
        $filePath=$upload->getRealPath();
        $file=fopen($filePath,'r');
        $csv = [];
        while($columns=fgetcsv($file, 1024, ';'))
        {
            $csv[] = $columns;
        }
        fclose($file);

        $this->dictionary->import($csv);
    }
}
