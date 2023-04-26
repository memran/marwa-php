<?php
	
	namespace App;
	
	
	use http\Params;
	use Marwa\Application\Exceptions\FileNotFoundException;
	use Marwa\Application\Exceptions\InvalidArgumentException;
	use Marwa\Application\Facades\Auth;
	
	class BackendController extends Controller {
		
		/**
		 * @var string
		 */
		protected $_theme = 'backend/';
		/**
		 * @var array
		 */
		protected $_data = [];
		
		/**
		 * @param string $tplFileName
		 * @param array $data
		 * @return mixed
		 * @throws FileNotFoundException
		 * @throws InvalidArgumentException
		 */
		public function render( string $tplFileName, array $data = [] )
		{
			
			$this->_data['title'] = env('APP_TITLE');
			if ( Auth::isLoggedIn() )
			{
				if ( !is_null(Auth::user()) )
				{
					$this->_data['auth_user'] = Auth::user()->toArray();
				}
				else
				{
					return Auth::logout();
				}
				
			}
			
			if ( !empty($data) )
			{
				$this->_data = array_merge($this->_data, $data);
			}
			
			return parent::render($this->getThemeFolder() . $tplFileName, $this->_data);
			
		}
		
		/**
		 * @return string
		 * @throws InvalidArgumentException
		 */
		protected function getThemeFolder()
		{
			return $this->_theme . env('ADMIN_THEME') . '/';
		}
		
		/**
		 * @param $key
		 * @param $value
		 * @return $this
		 */
		public function setData( $key, $value )
		{
			$this->_data[ $key ] = $value;
			
			return $this;
		}
		
		/**
		 * @param $method_name
		 * @param $policy_name
		 * @param array $params
		 * @return mixed
		 */
		protected function authorize($method_name,$policy_name,$params=[])
		{
			return call_user_func_array([$policy_name,$method_name],$params);
		}
		
	}
