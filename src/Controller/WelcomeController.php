<?php
declare(strict_types=1);
namespace App\Controller;
use Cake\Event\EventInterface;

class WelcomeController extends AppController
{

    public function initialize(): void
    {
        $this->loadComponent('Response');
        $this->loadComponent('RequestHandler');
    }

    public function welcome()
	{
		if ($this->request->is(['post','get'])) {
			$http_code = 200;
			$message = 'welcome to amp api service.';
			$response = $this->Response->Response($http_code, $message, null, null);
			return $this->responseBody($http_code, $response);
		}
	}
}
