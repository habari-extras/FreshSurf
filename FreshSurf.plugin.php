<?php
/*Fresh Surf
 * A plugin to show your most recent del.icio.us posts
 */

/*

Use in theme:

     <ul>
     <?php foreach ($delicious->post as $item): ?>
     <li><a href="<?php echo $item['href']; ?>"><?php echo $item['description']; ?></a></li>
     <?php endforeach; ?>
     </ul>


*/

class FreshSurf extends Plugin{

	const BASE_URL = 'api.del.icio.us/v1/';

	/**
	* Required Plugin Information
	*/
	public function info()
	{
		return array('name' => 'Fresh Surf',
			'version' => '0.1',
			'url' => 'http://habariproject.org/',
            'author' => 'Habari Community',
            'authorurl' => 'http://habariproject.org/',
            'license' => 'Apache License 2.0',
			'description' => 'Recent del.icio.us posts',
			'copyright' => '2007'
			);
	}
	/**
	* Add actions to the plugin page for this plugin
	*
	* @param array $actions An array of actions that apply to this plugin
	* @param string $plugin_id The string id of a plugin, generated by the system
	* @return array The array of actions to attach to the specified $plugin_id
	*/
	public function filter_plugin_config($actions, $plugin_id)
	{
		if ($plugin_id == $this->plugin_id()){
			$actions[] = 'Configure';
		}

		return $actions;
	}

	/**
	* Respond to the user selecting an action on the plugin page
	*
	* @param string $plugin_id The string id of the acted-upon plugin
	* @param string $action The action string supplied via the filter_plugin_config hook
	*/
	public function action_plugin_ui($plugin_id, $action)
	{
		if ($plugin_id == $this->plugin_id()){
			switch ($action){
				case 'Configure' :
					$ui = new FormUI(strtolower(get_class($this)));
					$delicious_username = $ui->add('text', 'username', 'del.icio.us Username:');
					$delicious_password = $ui->add('password', 'password', 'del.icio.us Password:');
					$delicious_count = $ui->add('text', 'count' , 'number of posts to show');
					$ui->on_success(array($this, 'updated_config'));
					$ui->out();
					break;
			}
		}
	}

	/**
	* Returns true if plugin config form values defined in action_plugin_ui should be stored in options by Habari
	*
	* @return boolean True if options should be stored
	*/
	public function updated_config($ui)
	{
		return true;
	}

	function action_add_template_vars($theme)
	{
		$username = Options::get('freshsurf:username');
		$password = Options::get('freshsurf:password');
		$count = Options::get('freshsurf:count');

		if($username != '' && $password != '') {
			if(Cache::has('freshsurf:' . $username)) {
				$response = Cache::get('freshsurf:' . $username);
			}
			else {
				$request = new RemoteRequest("https://{$username}:{$password}@" . self::BASE_URL . "posts/recent?count={$count}", 'GET', 20);
				$request->execute();
				$response = $request->get_response_body();
				Cache::set('freshsurf:' . $username, $response);
			}

			$delicious = @simplexml_load_string($response);
			if($delicious instanceof SimpleXMLElement) {
				$theme->delicious = $delicious;
			}
			else {
				$theme->delicious = @simplexml_load_string('<posts><post href="#" description="Could not load feed from delicious.  Is username/password correct?"/></posts>');
				Cache::expire('freshsurf:' . $username);
			}
		}
		else {
			$theme->delicious = @simplexml_load_string('<posts></posts>');
		}
	}
}

?>
