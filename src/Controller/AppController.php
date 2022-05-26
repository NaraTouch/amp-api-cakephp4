<?php
declare(strict_types=1);

namespace App\Controller;
use Cake\Controller\Controller;

class AppController extends Controller
{
	public function initialize(): void
	{
		parent::initialize();
		$this->viewBuilder(false);
		$this->loadComponent('RequestHandler');
	}

	public function responseBody($error_code = null, $response = [])
	{
		$http_code = 404;
		if ($error_code) {
			$http_code = $error_code;
		}
		return $this->response->withType('application/json')
			->withStatus($http_code)
			->withStringBody(json_encode($response));
	}
}
