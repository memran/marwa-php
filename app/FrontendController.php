<?php
	
	namespace App;
	
	
	use Marwa\Application\Exceptions\InvalidArgumentException;
	use Marwa\Application\Facades\DB;
	
	class FrontendController extends Controller {
		
		/**
		 * @var string
		 */
		protected $_theme = 'frontend/';
		/**
		 * @var array
		 */
		protected $_data = [];
		
		/**
		 * FrontendController constructor.
		 */
		public function __construct()
		{
			$this->loadSiteSettings();
		}
		
		/**
		 * @param string $tplFileName
		 * @param array $data
		 * @return mixed
		 * @throws InvalidArgumentException
		 * @throws \Marwa\Application\Exceptions\FileNotFoundException
		 */
		public function render( string $tplFileName, array $data = [] )
		{
			if ( !empty($data) )
			{
				$this->_data = array_merge($this->_data, $data);
			}
			
			return parent::render($this->getThemeFolder() . $tplFileName, $this->_data);
			
		}
		
		/**
		 *
		 */
		protected function loadSiteSettings()
		{
			$settings = DB::raw("SELECT * FROM cms_settings");
			foreach ( $settings as $key => $val )
			{
				$this->_data[ $val['site_key'] ] = $val['site_value'];
			}
		}
		
		/**
		 * @return string
		 * @throws InvalidArgumentException
		 */
		protected function getThemeFolder()
		{
			return $this->_theme . env('FRONT_THEME') . '/';
		}
	}
