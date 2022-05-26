<?php
declare(strict_types=1);
namespace App\Controller;
use Cake\Event\EventInterface;

class ErrorController extends AppController
{

	public function initialize(): void
	{
		$this->loadComponent('RequestHandler', [
			'enableBeforeRedirect' => false,
		]);
		$this->autoRender = false;
		$this->loadComponent('Response');
	}
	public function beforeRender(EventInterface $event)
	{
		parent::beforeRender($event);
		$this->viewBuilder()->setTemplatePath('Error');
	}
	
	public function afterFilter(EventInterface $event)
	{
	}
}
