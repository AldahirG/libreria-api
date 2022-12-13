<?php

namespace App\Http\Controllers;

use App\Models\Author;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

use Illuminate\Http\Request;

class AuthorController extends Controller
{
    public function index()
    {
        $author = Author::all();
        return $this->getResponse200($author);
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
        $author = new Author();
        $author->name = $request->name;
        $author->first_surname = $request->first_surname;
        $author->second_surname = $request->second_surname;
        $author->save();
        $response = $this->getResponse201("author", "created", $author);
        return $response;
    }

    public function update(Request $request, $id)
    {
        $response = $this->response();
        $author = Author::find($id);

        DB::beginTransaction();
        try {

            if ($author != null) {
                $author->name = $request->name;
                $author->first_surname = $request->first_surname;
                $author->second_surname = $request->second_surname;
                $author->update();

                $response = $this->getResponse201("author", "updated", $author);
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
        $author = Author::find($id);
        if ($author) {
            $response = $this->getResponse200($author);
        } else {
            $response = $this->getResponse404();
        }
        return $response;
    }

    public function destroy($id)
    {
        $response = $this->getResponse404();
        $author = Author::find($id);
        try {
            if ($author != null) {
                $author->books()->detach();
                $author->delete();
                $response = $this->getResponseDelete200("author");
            } else {
                $response = $this->getResponse404();
            }
        } catch (Exception $ex) {
            $response = $this->getResponse500([$ex->getMessage()]);
            var_dump($ex);
        }
        return $response;
    }
}
