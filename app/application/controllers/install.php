<?php

class Install extends CI_Controller {

	function __construct()
    {
         parent::__construct();
    }

	function complete()
	{
		set_time_limit(0);

		require(FCPATH . 'app' .
							DIRECTORY_SEPARATOR . 'koken' .
							DIRECTORY_SEPARATOR . 'schema.php');

		include(FCPATH . 'app' . DIRECTORY_SEPARATOR . 'koken' . DIRECTORY_SEPARATOR . 'DarkroomUtils.php');

		$old_db_config = FCPATH . 'storage/configuration/database.php';

		if (isset($_POST['database']))
		{
			$database_config = array_merge(array(
				'driver' => 'mysqli',
				'hostname' => 'localhost',
				'database' => 'koken',
				'username' => '',
				'password' => '',
				'prefix' => '',
				'socket' => ''
			), $_POST['database']);
		}
		else if (file_exists($old_db_config))
		{
			include $old_db_config;
			$database_config = $KOKEN_DATABASE;
		}

		Shutter::write_db_configuration($database_config);

		$this->load->database();
		$this->load->dbforge();

		foreach($koken_tables as $table_name => $info)
		{
			if (!isset($info['no_id']))
			{
				$this->dbforge->add_field('id');
			}

			foreach($info['fields'] as $name => &$attr)
			{
				if (in_array(strtolower($attr['type']), array('text', 'varchar', 'longtext')) && $name !== 'id')
				{
					$attr['null'] = true;
				}
			}

			$this->dbforge->add_field($info['fields']);
			foreach($info['keys'] as $key)
			{
				$primary = false;
				if ($key == 'id')
				{
					$primary = true;
				}
				$this->dbforge->add_key($key, $primary);
			}
			$this->dbforge->create_table($database_config['prefix'] . "$table_name");

			if (isset($info['uniques']))
			{
				$table = $database_config['prefix'] . "$table_name";
				foreach($info['uniques'] as $key)
				{
					if (is_array($key))
					{
						$name = join('_', $key);
						$key = join(',', $key);
					}
					else
					{
						$name = $key;
					}
					$this->db->query("CREATE UNIQUE INDEX $name ON $table ($key)");
				}
			}
		}

		$this->load->library('datamapper');

		$fullname = $_POST['first_name'] . ' ' . $_POST['last_name'];

		$settings = array(
			'site_timezone' => $_POST['timezone'],
			'console_show_notifications' => 'yes',
			'console_enable_keyboard_shortcuts' => 'yes',
			'uploading_default_license' => 'all',
			'uploading_default_visibility' => 'public',
			'uploading_default_album_visibility' => 'public',
			'uploading_default_max_download_size' => 'none',
			'uploading_publish_on_captured_date' => 'false',
			'site_title' => $fullname,
			'site_page_title' => $fullname,
			'site_tagline' => 'Your site tagline',
			'site_copyright' => '© ' . $fullname,
			'site_description' => '',
			'site_keywords' => 'photography, ' . $fullname,
			'site_date_format' => 'F j, Y',
			'site_time_format' => 'g:i a',
			'site_privacy' => 'public',
			'site_hidpi' => 'true',
			'site_url' => 'default',
			'use_default_labels_links' => 'true',
			'uuid' => md5($_SERVER['HTTP_HOST'] . uniqid('', true)),
			'retain_image_metadata' => 'false',
			'image_use_defaults' => 'true',
			'image_tiny_quality' => '80',
			'image_small_quality' => '80',
			'image_medium_quality' => '85',
			'image_medium_large_quality' => '85',
			'image_large_quality' => '85',
			'image_xlarge_quality' => '90',
			'image_huge_quality' => '90',
			'image_tiny_sharpening' => '0.7',
			'image_small_sharpening' => '0.6',
			'image_medium_sharpening' => '0.6',
			'image_medium_large_sharpening' => '0.6',
			'image_large_sharpening' => '0.6',
			'image_xlarge_sharpening' => '0.3',
			'image_huge_sharpening' => '0',
			'last_upload' => 'false',
			'last_migration' => '42',
			'has_toured' => false,
			'email_handler' => 'DDI_Email',
			'email_delivery_address' => $_POST['email'],
		);

		if (isset($_POST['image_processing']))
		{
			$settings['image_processing_library'] = $_POST['image_processing'];
		}

		foreach($settings as $name => $value)
		{
			$u = new Setting;
			$u->name = $name;
			$u->value = $value;
			$u->save();
		}

		$urls = array(
			array(
				'type' => 'content',
				'data' => array(
					'singular' => 'Content',
					'plural' => 'Content',
					'order' => 'published_on DESC',
					'url' => 'slug',
				)
			),
			array(
				'type' => 'favorite',
				'data' => array(
					'singular' => 'Favorite',
					'plural' => 'Favorites',
					'order' => 'manual ASC'
				)
			),
			array(
				'type' => 'feature',
				'data' => array(
					'singular' => 'Feature',
					'plural' => 'Features',
					'order' => 'manual ASC'
				)
			),
			array(
				'type' => 'album',
				'data' => array(
					'singular' => 'Album',
					'plural' => 'Albums',
					'order' => 'manual ASC',
					'url' => 'slug'
				)
			),
			array(
				'type' => 'set',
				'data' => array(
					'singular' => 'Set',
					'plural' => 'Sets',
					'order' => 'title ASC'
				)
			),
			array(
				'type' => 'essay',
				'data' => array(
					'singular' => 'Essay',
					'plural' => 'Essays',
					'order' => 'published_on DESC',
					'url' => 'date+slug'
				)
			),
			array(
				'type' => 'page',
				'data' => array(
					'singular' => 'Page',
					'plural' => 'Pages',
					'url' => 'slug'
				)
			),
			array(
				'type' => 'tag',
				'data' => array(
					'singular' => 'Tag',
					'plural' => 'Tags'
				)
			),
			array(
				'type' => 'category',
				'data' => array(
					'singular' => 'Category',
					'plural' => 'Categories'
				)
			),
			array(
				'type' => 'timeline',
				'data' => array(
					'singular' => 'Timeline',
					'plural' => 'Timeline'
				)
			)
		);

		$u = new Url;
		$u->data = serialize($urls);
		$u->save();

		$u = new User();
		$u->password = $_POST['password'];
		$u->email = $_POST['email'];
		$u->first_name = $_POST['first_name'];
		$u->last_name = $_POST['last_name'];
		$u->permissions = 4;
		$u->save();

		$theme = new Draft;
		$theme->path = isset($_POST['theme']) ? $_POST['theme'] : 'elementary';
		$theme->current = 1;
		$theme->draft = 1;
		$theme->init_draft_nav();
		$theme->live_data = $theme->data;
		$theme->save();

		$h = new History();
		$h->message = 'system:install';
		$h->save($u->id);

		if (ENVIRONMENT === 'development')
		{
			$app = new Application();
			$app->token = '69ad71aa4e07e9338ac49d33d041941b';
			$app->role = 'read-write';
			$app->save();
		}

		$path = str_replace('api.php', 'app/application/httpd', $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME']);
		$ch = curl_init($path);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_NOBODY, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$c = curl_exec($ch);

		$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if ($code != 500 && $code != 403)
		{
			$htaccess = create_htaccess();
			file_put_contents(FCPATH . '.htaccess', $htaccess, FILE_APPEND);
		}

		$base_folder = trim(preg_replace('/\/api\.php(.*)?$/', '', $_SERVER['SCRIPT_NAME']), '/');

		$libs = DarkroomUtils::libraries();

		if (isset($settings['image_processing_library']))
		{
			$processing_string = $libs[$settings['image_processing_library']]['label'];
		}
		else
		{
			$lib = array_shift($libs);
			$processing_string = $lib['label'];
		}

		$this->load->library('webhostwhois');

		$host = new WebhostWhois(array(
			'useDns' => false
		));

		if ($host->key === 'unknown' && isset($_SERVER['KOKEN_HOST']))
		{
			$host->key = $_SERVER['KOKEN_HOST'];
		}

		$data = array(
			'domain' => $_SERVER['HTTP_HOST'],
			'path' => '/' . $base_folder,
			'uuid' => $settings['uuid'],
			'php' => PHP_VERSION,
			'version' => KOKEN_VERSION,
			'ip' => $_SERVER['SERVER_ADDR'],
			'image_processing' => urlencode($processing_string),
			'subscribe' => (isset($_POST['subscribe']) ? $_POST['subscribe'] : ''),
			'first' => $_POST['first_name'],
			'last' => $_POST['last_name'],
			'host' => $host->key,
			'plugins' => array(),
		);

		$t = new Theme;
		$themes = $t->read();

		foreach($themes as $theme)
		{
			if (isset($theme['koken_store_guid']))
			{
				$data['plugins'][] = array(
					'guid' => $theme['koken_store_guid'],
					'version' => $theme['version'],
				);
			}
		}

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, KOKEN_STORE_URL . '/register');
		curl_setopt($curl, CURLOPT_POST, 1);
	    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data, '', '&'));
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		$r = curl_exec($curl);
		curl_close($curl);

		$key = md5($_SERVER['HTTP_HOST'] . uniqid('', true));
		Shutter::write_encryption_key($key);

		Shutter::write_cache('plugins/compiled.cache',  serialize(array(
			'info' => array('email_delivery_address' => $_POST['email']),
			'plugins' => array()
		)));

		Shutter::hook('install.complete');

		header('Content-type: application/json');
		die( json_encode(array('success' => true)) );
	}
}

/* End of file install.php */
/* Location: ./system/application/controllers/install.php */
