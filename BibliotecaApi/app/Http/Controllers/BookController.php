<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\DB;

class BookController extends Controller
{
    public function index()
    {
        //$books = Book::all();
        $books = Book::with('bookDownload', 'category', 'editorial', 'authors')->get();
        return $this->getResponse200($books);
    }

    public function response()
    {
        return [
            "error" => true,
            "message" => "",
            "data" => []
        ];
    }

    public function store(Request $request)
    {
        $response = $this->response();
        $isbn = trim($request->isbn);
        $existIsbn = Book::where('isbn', $isbn)->exists();
        if (!$existIsbn) {
            $book = new Book();
            $book->isbn = $isbn;
            $book->title = $request->title;
            $book->description = $request->description;
            $book->published_date = Carbon::now();
            $book->category_id = $request->category['id'];
            $book->editorial_id = $request->editorial['id'];
            $book->save();
            foreach ($request->authors as $item) {
                $book->authors()->attach($item);
            }
            $response = $this->getResponse201("book", "created", $book);
        } else {
            $response = $this->getResponse400("ISBN duplicated!");
        }
        return $response;
    }

    public function update(Request $request, $id)
    {
        $response = $this->response();
        $book = Book::find($id);

        DB::beginTransaction();
        try {

            if ($book) {
                $isbn = trim($request->isbn);
                $isbnOwner = Book::where('isbn', $isbn)->first();
                if (!$isbnOwner || $isbnOwner->id == $book->id) {
                    $book->isbn = $isbn;
                    $book->title = $request->title;
                    $book->description = $request->description;
                    $book->published_date = Carbon::now();
                    $book->category_id = $request->category['id'];
                    $book->editorial_id = $request->editorial['id'];
                    $book->update();
                    //Delete
                    foreach ($book->authors as $item) {
                        $book->authors()->detach($item);
                    }
                    //Add new authors
                    foreach ($request->authors as $item) {
                        $book->authors()->attach($item);
                    }
                    $book = Book::with('category', 'editorial', 'authors')->where('id', $id)->get();
                    $response = $this->getResponse201("book", "updated", $book);
                } else {
                    $response = $this->getResponse400("ISBN duplicated!");
                }
            } else {
                $response = $this->getResponse400("Not found");;
            }

            DB::commit();
        } catch (Exception $e) {
            $response = $this->getResponse500("Rollback transaction");
            DB::rollBack();
        }
        return $response;
    }


    public function show($id)
    {
        $book = Book::find($id);
        $response = $this->getResponse200($book);
        return $response;
    }

    public function destroy($id)
    {

        $book = Book::find($id);

        try {
            if ($book != null) {

                $book->authors()->detach();
                $book->delete();
                $response = $this->getResponseDelete200("book");
            } else {
            }
        } catch (Exception $ex) {
            $response = $this->getResponse500();
        }
        return $response;
    }
}
