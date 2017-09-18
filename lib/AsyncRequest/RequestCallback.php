<?php

namespace AsyncRequest;

class RequestCallback
{
	/** @var IRequest */
	private $request;

	/** @var ?callable */
	private $callback;

	public function __construct(IRequest $request, $callback = null)
	{
		if ($callback !== null && !is_callable($callback)) {
			throw new \InvalidArgumentException('Invalid callback');
		}

		$this->request = $request;
		$this->callback = $callback;
	}

	public function getRequest()
	{
		return $this->request;
	}

	public function getCallback()
	{
		return $this->callback;
	}
}
