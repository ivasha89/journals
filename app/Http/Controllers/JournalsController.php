<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Journal;
use App\Author;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class JournalsController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $journals = Journal::orderBy('relise_date', 'desc')->get();
        $authors = Author::all();
        return view('public/journals/index')
            ->withjournals($journals)
            ->withauthors($authors);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $authors = Author::all();
        foreach ($authors as $key => $author) {
            $array[$key] = [
                'ind' => 0,
                'name' => $author->full_name,
                'id' => $author->id
            ];
        }
        return view('public/journals/edit')
            ->withjournal(null)
            ->withauthors($array);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $authors = implode(',',$request->authors);
        $request['authors'] = $authors;
        $journal = Journal::create($request->all());
        $file = $request->file('image');
        if (isset($file)) {
            Storage::disk('public')->putFileAs("/journals/$journal->id", $file, $file->getClientOriginalName());
            $journal->update([
                'image' => "/journals/" . $journal->id . '/' . $file->getClientOriginalName()
            ]);
        }

        return redirect('/journals')->withErrors('Журнал '. $journal->title .' сохранён', 'message');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $authors = Author::all();
        try {
            $journal = Journal::findOrFail($id);
            $journalAuthors = explode(',',$journal->authors);
            foreach ($authors as $key => $author) {
                foreach($journalAuthors as $authorId) {
                    if ($author->id == $authorId) {
                        $array[$key] = [
                            'ind' => 1,
                        ];
                    }
                    else {
                        $array[$key] = [
                            'ind' => 0,
                        ];
                    }
                    $array[$key] += [
                        'name' => $author->full_name,
                        'id' => $author->id
                    ];
                }
            }
        }
        catch (\Throwable $exception) {
            $journal = $exception;
        }

        return view('public/journals/edit')
            ->withjournal($journal)
            ->withauthors($array);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        try {
            $authors = implode(',',$request->authors);
            $request['authors'] = $authors;
            $journal = Journal::findOrFail($id);
            $file = $request->file('image');
            if (isset($file)) {
                Storage::disk('public')->delete($journal->image);
                Storage::disk('public')->putFileAs("/journals/$journal->id", $file, $fileName);
                $journal->update([
                    'image' => "/journals/" . $journal->id . '/' . $fileName
                ]);
            }
            $journal->update($request->except('image'));

            return redirect('/journals')->withErrors('Журнал '. $journal->title .' сохранён', 'message');
        }
        catch (\Throwable $exception) {
            return redirect('/journals')->withErrors(is_object($exception) ? $exception->getMessage() : $exception,'error');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        try {
            $journal = Journal::findOrFail($id);
            Storage::deleteDirectory("public/journals/$journal->id");
            $journal->delete();
            $response = 'Журнал ' . $journal->title . ' Удален';
            return redirect('/journals')->withErrors($response, 'message');
        }
        catch (\Throwable $exception) {
            $response = $exception;
            return redirect('/journals')->withErrors($response, 'error');
        }
    }
}
