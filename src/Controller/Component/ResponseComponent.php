<?php
namespace App\Controller\Component;
use Cake\Controller\Component;


class ResponseComponent extends Component
{
	
	public function initialize(array $config): void
	{
		parent::initialize($config);
	}

	public function Response($http_code = null, $message = null, $data = [], $error = [])
	{
		$http_status = (int) $http_code;
		$response = [
			'ErrorCode' => $http_status,
			'Message' => $message,
			'Data' => $data,
			'Error' => $error
		];
		return $response;
	}

	public function Message($http_code = null)
	{
		$http_status = (int) $http_code;
		$message = '';
		if ($http_status == 400) {
			$message = 'Bad Request';
		} else if ($http_status == 401 || $http_status == 402) {
			$message = 'Unauthorized.';
		} else if ($http_status == 403) {
			$message = 'Forbidden';
		} else if ($http_status == 404) {
			$message = 'Not Found';
		} else if ($http_status == 405) {
			$message = 'Method Not Allowed';
		} else if ($http_status == 408) {
			$message = 'Request Timeout';
		} else if ($http_status == 500) {
			$message = 'Internal Server Error';
		} else if ($http_status == 502) {
			$message = 'Bad Gateway';
		} else if ($http_status == 503) {
			$message = 'Service Unavailable';
		} else if ($http_status == 511) {
			$message = 'Network Authentication Required';
		} else {
			$message = 'Unknow Error';
		}
		$response = [
			'ErrorCode' => $http_status,
			'Message' => $message,
		];
		return $response;
	}
}