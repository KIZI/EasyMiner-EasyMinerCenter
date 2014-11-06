<?php

namespace KBI;

class RESTClientResponse {
	protected $xml;
	protected $body;
	protected $info;

	public function getBody() {
		return $this->body;
	}

	public function getBodyAsXml() {
		if ($this->xml == null) {
			$this->xml = simplexml_load_string($this->getBody());
		}

		return $this->xml;
	}

	public function getStatusCode() {
		return $this->info['http_code'];
	}

	public function getStatus() {
		$code = $this->getStatusCode();

		return "{$code} {$this->getStatusText($code)}";
	}

	public function isSuccess() {
		return $this->getStatusCode() < 300;
	}

	public function __construct($body, $info)
	{
		$this->body = $body;
		$this->info = $info;
	}

	/**
	 * Source: http://en.wikipedia.org/wiki/List_of_HTTP_status_codes
	 * @param int $code
	 * @return string
	 */
	protected function getStatusText($code)
	{
		switch($code)
		{
			// 1xx Informational
			case 100: return 'Continue'; break;
			case 101: return 'Switching Protocols'; break;
			case 102: return 'Processing'; break; // WebDAV
			case 122: return 'Request-URI too long'; break; // Microsoft

			// 2xx Success
			case 200: return 'OK'; break;
			case 201: return 'Created'; break;
			case 202: return 'Accepted'; break;
			case 203: return 'Non-Authoritative Information'; break; // HTTP/1.1
			case 204: return 'No Content'; break;
			case 205: return 'Reset Content'; break;
			case 206: return 'Partial Content'; break;
			case 207: return 'Multi-Status'; break; // WebDAV

			// 3xx Redirection
			case 300: return 'Multiple Choices'; break;
			case 301: return 'Moved Permanently'; break;
			case 302: return 'Found'; break;
			case 303: return 'See Other'; break; //HTTP/1.1
			case 304: return 'Not Modified'; break;
			case 305: return 'Use Proxy'; break; // HTTP/1.1
			case 306: return 'Switch Proxy'; break; // Depreciated
			case 307: return 'Temporary Redirect'; break; // HTTP/1.1

			// 4xx Client Error
			case 400: return 'Bad Request'; break;
			case 401: return 'Unauthorized'; break;
			case 402: return 'Payment Required'; break;
			case 403: return 'Forbidden'; break;
			case 404: return 'Not Found'; break;
			case 405: return 'Method Not Allowed'; break;
			case 406: return 'Not Acceptable'; break;
			case 407: return 'Proxy Authentication Required'; break;
			case 408: return 'Request Timeout'; break;
			case 409: return 'Conflict'; break;
			case 410: return 'Gone'; break;
			case 411: return 'Length Required'; break;
			case 412: return 'Precondition Failed'; break;
			case 413: return 'Request Entity Too Large'; break;
			case 414: return 'Request-URI Too Long'; break;
			case 415: return 'Unsupported Media Type'; break;
			case 416: return 'Requested Range Not Satisfiable'; break;
			case 417: return 'Expectation Failed'; break;
			case 422: return 'Unprocessable Entity'; break; // WebDAV
			case 423: return 'Locked'; break; // WebDAV
			case 424: return 'Failed Dependency'; break; // WebDAV
			case 425: return 'Unordered Collection'; break; // WebDAV
			case 426: return 'Upgrade Required'; break;
			case 449: return 'Retry With'; break; // Microsoft
			case 450: return 'Blocked'; break; // Microsoft

			// 5xx Server Error
			case 500: return 'Internal Server Error'; break;
			case 501: return 'Not Implemented'; break;
			case 502: return 'Bad Gateway'; break;
			case 503: return 'Service Unavailable'; break;
			case 504: return 'Gateway Timeout'; break;
			case 505: return 'HTTP Version Not Supported'; break;
			case 506: return 'Variant Also Negotiates'; break;
			case 507: return 'Insufficient Storage'; break; // WebDAV
			case 509: return 'Bandwidth Limit Exceeded'; break; // Apache
			case 510: return 'Not Extended'; break;

			// Unknown code:
			default: return 'Unknown';  break;
		}
	}
}