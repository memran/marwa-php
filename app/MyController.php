<?php

namespace App;

use App\Mail\NewCustomer;
use App\Twillo\TwilloSMS;
use App\Broadcasts\Pusher;

use Psr\Http\Message\ResponseInterface;
use Marwa\Application\Facades\{View, Auth, File, App, Cache, Storage, Event, Redis, Mail, Notify, DB, Input};
use Marwa\Application\Utils\Validate;
use Marwa\Application\Views\Twig;
use Marwa\Application\Jobs\QueueTrait;
use Marwa\Application\Jobs\ScheduleTrait;
use Marwa\Application\Http\HttpClient;
use Marwa\Application\Response;

class MyController  extends Controller
{

	use QueueTrait;
	use ScheduleTrait;

	/**
	 *  Index function default route
	 */
	// public function index($request)
	// {
	//   $m = new Model();
	//   $m->update(['name'=>'demo:call','executed_at'=>now()->timestamp,'result'=>'testing'],'102');
	//   //$result = $m->all();
	//   $result=$m->paginate(10)->get();
	//   $links = $m->links();
	//   $page = $m->pageInArray();
	//   return view(
	//         'logs',
	//           [
	//            'results' => $result,
	//            'table' => 'Schedule Logs',
	//            'pagination' => $links,
	//            'page' => $page
	//           ]
	//        );

	// }

	/**
	 * [login description]
	 * @return [type] [description]
	 */
	// public function login()
	// {
	//    return view('login',
	//                 [
	//                 'title' => 'Welcome to RBC Admin',
	//                 'version' => App::version()
	//                 ]
	//             );

	// }


	/**
	 * function to login Authentication of User
	 *
	 * @throws \Exception
	 */
	public function auth()
	{
		$username = Input::post('usernameInput');
		$password = Input::post('passwordInput');

		//verify users
		if (!Auth::valid($username, $password)) {
			setMessage('error', 'Access Denied');

			return redirect('./login', 301);
		}

		logger('it works like a charm', [$username, $password]);

		setMessage('success', 'Successfully Logged In!');

		return redirect("./dashboard", 301);
	}


	public function dashboard()
	{
		return $this->render('dashboard');
	}

	public function logout()
	{
		setMessage("warning", "Logged Out!");

		return redirect('./login', 301);
	}


	public function index1()
	{

		// Mail::to(['memran.dhk@gmail.com'])
		//       ->subject('Test Email')
		//       ->from(['info@inception.com.bd'])
		//       ->send('Hello Buddy!');
		// logger('it works like a charm',['Marwa Framework'],'debug');
		// dd('done');
		// session('name','emran');
		// dd(session('name'));
		// app('session')->destroy();
		// cache('name','Marwa');
		// dd(cache('name'));
		// return view('index',[
		//       'app' => 'Marwa',
		//       'version' => App::version()
		//   ]);

		$r = DB::select('SELECT * FROM users');
		//$r = DB::delete('DELETE FROM users where id =:id',['id'=> 1]);
		// $r= DB::insert('insert into users (username,password,name) values (:username, :password,:name)',
		//             ['username'=>'admin2',
		//             'password'=> 'admin2',
		//             'name'=> "Mohammad Emran2"]);

		// $r= DB::update('UPDATE users SET username=:username, password=:password WHERE id=:id',
		//             ['username'=>'root',
		//             'password'=> 'root',
		//             'id'=> 4]);
		DB::logMessage();
		dd($r);

		return view('test', [
			'app' => 'Marwa',
			'version' => App::version()
		]);
	}


	public function dbtest(): ResponseInterface
	{
		dd(Users::table('users')->count());

		//$user = new Users();


		// dd($user->findAll());

		$data = [
			'username' => 'admin',
			'password' => Auth::getHash('admin'),
			'email' => 'jolpaihost@gmail.com',
			'name' => "Mohammad Emran",
			'status' => '1',
			'role_id' => 1,
			'country' => 'Bangladesh'
		];

		$insert = $user->create($data);

		if ($insert) {
			dd($user->findAll());
		} else {
			dd("failed to insert");
		}
	}

	public function pusher(): ResponseInterface
	{
		return view('pusher');
	}

	public function sendpush(): ResponseInterface
	{
		$result = Notify::broadcast()
			->process(new Pusher('my-channel', "my-event"));
		dd($result);
	}

	public function mailsend(): ResponseInterface
	{

		$result = Mail::to(['memran.dhk@gmail.com'])
			->send(new NewCustomer());
		dd($result);
	}

	public function smssend(): ResponseInterface
	{
		$result = Notify::sms()
			->process(new TwilloSMS('+8801713033045', "+12762218237", 'Hello World!It is MarwaPHP.'));
		dd($result);
	}
}
