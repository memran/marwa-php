<?php
namespace App;
use Marwa\Application\Response;

class TestController extends Controller {
	public function index()
	{
		return view('index',[ "version"=>"dev-main"]);
	}
}
