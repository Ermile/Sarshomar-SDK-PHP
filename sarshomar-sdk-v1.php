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

	public $language = 'en';

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
	public function request(string $_method, string $_url, $_parm = [], $lang = 'en')
	{

		$this->error_descreption = false;
		$this->error_code = false;
		$this->error_detail = false;
		$this->status = true;

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
		$curl_options[CURLOPT_HEADER] = true;
		$curl_options[CURLOPT_HTTPHEADER] = array("cache-control: no-cache");
		$curl_options[CURLOPT_HTTPHEADER][] = "authorization: " . $this->api_token;

		foreach ($this->headers as $key => $value) {
			$curl_options[CURLOPT_HTTPHEADER][] = "$key: $value";
		}
		$url = "https://sarshomar.com";
		if($lang != 'en'){
			$url .= "/" . $lang;
		}
		$url .= "/api/v";
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
		$header_len = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
		$header 	= substr($response, 0, $header_len);
		$body 		= substr($response, $header_len);
		$err 		= curl_error($curl);

		$headers = [];
		$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		foreach (explode("\n", $header) as $key => $value) {
			if($key == 0 && substr($value, 0, 5) == "HTTP/") continue;

			$array_of_header = explode(":", $value, 2);
			$array_of_header[0] = trim($array_of_header[0]);
			if($array_of_header[0] == '') continue;

			$headers[$array_of_header[0]] = isset($array_of_header[1]) ? trim($array_of_header[1]) : null;
		}
		if($err)
		{
			$this->make_error(curl_error($curl), curl_errno($curl));
			return new sarshomar_response(null, null, null, $this->error());
		}

		if(!$body)
		{
			$this->make_error("Response is empty!", 108);
			return new sarshomar_response(null, $headers, $status, $this->error());
		}

		if(!$json = json_decode($body, true))
		{
			$this->make_error("Response is not json syntax", null, $body);
			return new sarshomar_response(null, $headers, $status, $this->error());
		}

		if(!isset($json['status']))
		{
			$this->make_error("Response is has not valid arguments", 111);
			return new sarshomar_response($json, $headers, $status, $this->error());
		}

		if(!$json['status'])
		{
			$this->make_error($json['messages']['error'][0]['title'], 112, $json['messages']);
			return new sarshomar_response($json, $headers, $status, $this->error());
		}

		return new sarshomar_response($json, $header, $status, $this->error());
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
			$lang = isset($_args[2]) ? $_args[2] : $this->language;
			return $this->request($_name, $url, $parm, $lang);
		}
	}
}

class sarshomar_response
{
	public $response, $headers, $status, $error;

	public function __construct($_response, $_header, $_status, $_error)
	{
		$this->response = $_response;
		$this->headers 	= $_header;
		$this->status 	= $_status;
		$this->error 	= $_error;
	}

	public function response()
	{
		return $response;
	}

	public function result()
	{
		return isset($response['result']) ? $response['result'] : null;
	}

	public function status()
	{
		return $status;
	}

	public function error()
	{
		return $error;
	}
}
?>