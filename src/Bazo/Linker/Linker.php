<?php

namespace Bazo\Linker;

use Nette\Application\IRouter;
use Nette\Http\Request as HttpRequest;
use Nette\Http\Url;
use Nette\Application\Request;
use Nette\Application\IPresenterFactory;
use Nette\Application\UI\InvalidLinkException;
use Nette\Application\InvalidPresenterException;
use Nette\Application\UI\Presenter;



/**
 * Extracted from Nette Framework
 * @author Martin Bažík <martin@bazo.sk>
 * @link http://api.nette.org/2.1.0/source-Application.UI.Presenter.php.html#765 Original implementation
 */
class Linker
{

	/** @var  IRouter */
	private $router;

	/** @var HttpRequest */
	private $httpRequest;

	/** @var PresenterFactory */
	private $presenterFactory;



	public function __construct(IRouter $router, HttpRequest $httpRequest, IPresenterFactory $presenterFactory)
	{
		$this->router = $router;
		$this->httpRequest = $httpRequest;
		$this->presenterFactory = $presenterFactory;
	}


	/**
	 * @return string $url
	 */
	public function link($destination, $args = [])
	{
		static $presenterFactory = NULL, $router = NULL, $refUrl = NULL;

		if ($presenterFactory === NULL) {
			$presenterFactory = $this->presenterFactory;
			$router = $this->router;
			$refUrl = new Url($this->httpRequest->getUrl());
			$refUrl->setPath($this->httpRequest->getUrl()->getScriptPath());
		}

		// PARSE DESTINATION
		// 1) fragment
		$a = strpos($destination, '#');
		if ($a === FALSE) {
			$fragment = '';
		} else {
			$fragment = substr($destination, $a);
			$destination = substr($destination, 0, $a);
		}

		// 2) ?query syntax
		$a = strpos($destination, '?');
		if ($a !== FALSE) {
			parse_str(substr($destination, $a + 1), $args); // requires disabled magic quotes
			$destination = substr($destination, 0, $a);
		}

		// 3) URL scheme
		$a = strpos($destination, '//');
		if ($a === FALSE) {
			$scheme = FALSE;
		} else {
			$scheme = substr($destination, 0, $a);
			$destination = substr($destination, $a + 2);
		}

		if ($destination == NULL) {  // intentionally ==
			throw new InvalidLinkException("Destination must be non-empty string.");
		}

		// 5) presenter: action
		$a = strrpos($destination, ':');

		$action = (string) substr($destination, $a + 1);
		if ($destination[0] === ':') { // absolute
			if ($a < 2) {
				throw new InvalidLinkException("Missing presenter name in '$destination'.");
			}
			$presenter = substr($destination, 1, $a - 1);
		} else { // relative
			throw new InvalidLinkException("Destination must be absolute.");
		}
		try {
			$presenterFactory->getPresenterClass($presenter);
		} catch (InvalidPresenterException $e) {
			throw new InvalidLinkException($e->getMessage(), NULL, $e);
		}

		// ADD ACTION & SIGNAL & FLASH
		$args[Presenter::ACTION_KEY] = $action;

		$request = new Request($presenter, Request::FORWARD, $args, [], []);

		$url = $router->constructUrl($request, $refUrl);
		if ($url === NULL) {
			unset($args[Presenter::ACTION_KEY]);
			$params = urldecode(http_build_query($args, NULL, ', '));
			throw new InvalidLinkException("No route for $presenter:$action($params)");
		}

		return $url . $fragment;
	}


}
