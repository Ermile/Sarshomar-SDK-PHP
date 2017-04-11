<?php
namespace sarshomar;

/**
 * sarshomar.com sdk
 * @version v0.1 first offical version
 */
class sdk
{
	private $api_token, $version, $error_detail;

	private $error_descreption, $error_code, $status = true;

	public $headers = [];

	/**
	 * configurate for requests
	 * @param array $_config
	 */
	public function __construct(array $_config)
	{
		if(!isset($_config['api_token']))
		{
			return $this->make_error("Api token not found", 101);
		}
		if(!isset($_config['version']))
		{
			return $this->make_error("Version app not found", 102);
		}
		if(gettype($_config['api_token']) != 'string')
		{
			return $this->make_error("Api token must be string", 103);
		}
		if(!in_array(gettype($_config['version']), ['double', 'integer']))
		{
			return $this->make_error("Version app must be double or integer", 104);
		}
		$this->api_token 	= $_config['api_token'];
		$this->version 		= $_config['version'];
	}

	/**
	 * request corridor for connection to sarshomar.com/api
	 * @param  string $_method 	http method
	 * @param  string $_url 	api url
	 * @param  array  $_parm 	request body or parm
	 * @return array  			response
	 */
	public function request(string $_method, string $_url, $_parm = [])
	{
		$_method = strtoupper($_method);

		if(!in_array($_method, ['GET', 'POST', 'PUT', 'DELETE', 'PATCH']))
		{
			return $this->make_error("method `$_method` not support for this sdk", 106);
		}

		if(!is_array($_parm))
		{
			return $this->make_error("request data must be array", 107);
		}

		$curl = curl_init();

		$curl_options = array();

		$curl_options[CURLOPT_RETURNTRANSFER] = true;
		$curl_options[CURLOPT_TIMEOUT] = 60;
		$curl_options[CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_1_1;
		$curl_options[CURLOPT_CUSTOMREQUEST] = $_method;
		$curl_options[CURLOPT_HTTPHEADER] = array("cache-control: no-cache");
		$curl_options[CURLOPT_HTTPHEADER][] = "authorization: " . $this->api_token;

		foreach ($this->headers as $key => $value) {
			$curl_options[CURLOPT_HTTPHEADER][] = "$key: $value";
		}

		$url = "https://sarshomar.com/api/v";
		$url .= $this->version;
		$url .= '/' . $_url;

		if(($_method == 'GET' || $_method == 'DELETE') && !empty($_parm))
		{
			$url .= "?";
			$array_parm = array();
			foreach ($_parm as $key => $value) {
				$array_parm[] = "$key=$value";
			}
			$url .= join("&", $array_parm);
		}
		elseif(!empty($_parm))
		{
			$curl_options[CURLOPT_POSTFIELDS] = json_encode($_parm);
			$curl_options[CURLOPT_HTTPHEADER][] = "content-type: application/json";
		}

		$curl_options[CURLOPT_URL] = $url;
		curl_setopt_array($curl, $curl_options);

		$response 	= curl_exec($curl);
		$err 		= curl_error($curl);

		if($err)
		{
			return $this->make_error(curl_error($curl), curl_errno($curl));
		}

		if(!$response)
		{
			return $this->make_error("Response is empty!", 108);
		}

		if(!$json = json_decode($response, true))
		{
			return $this->make_error("Response is not json syntax", 109);
		}

		if(!isset($json['status']))
		{
			return $this->make_error("Response is has not valid arguments", 111);
		}

		if(!$json['status'])
		{
			return $this->make_error($json['messages']['error'][0]['title'], 112, $json['messages']);
		}

		if(isset($json['result']))
		{
			return $json['result'];
		}

		return $json;
	}

	/**
	 * make temp login url for connect user
	 * @param  string $_token temp token
	 * @return string         access temp token url
	 */
	public function token_link(string $_token)
	{
		return "https://sarshomar.com/referer?to=token:" . $_token;
	}

	/**
	 * error maker sdk
	 * @param  string $_error_descreption descreption of error
	 * @param  integer $_error_code        error code
	 * @return array                     array of descreption and error code
	 */
	private function make_error($_error_descreption = null, $_error_code = null, $_error_detail = null)
	{
		$this->status 				= 0;

		$this->error_descreption 	= $_error_descreption;
		$this->error_code 			= $_error_code;
		$this->error_detail 		= $_error_detail;

		return $this->error();
	}

	/**
	 * get error
	 * @return array                     array of descreption and error code
	 */
	public function error()
	{
		if($this->status)
		{
			return false;
		}

		return array(
			'status' 			=> $this->status,
			'error_descreption' => $this->error_descreption,
			'error_code' 		=> $this->error_code,
			'error_detail' 		=> $this->error_detail
			);
	}

	/**
	 * [__call description]
	 * @param  [type] $_name [description]
	 * @param  [type] $_args [description]
	 * @return [type]        [description]
	 */
	public function __call($_name, $_args)
	{
		if(in_array($_name, ['get', 'post', 'put', 'delete', 'patch']))
		{
			if(!isset($_args[0]))
			{
				return $this->make_error("Url nof found in request command", 105);
			}
			$url = $_args[0];
			$parm = isset($_args[1]) ? $_args[1] : [];
			return $this->request($_name, $url, $parm);
		}
	}
}
?>