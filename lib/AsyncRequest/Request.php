<?php

namespace AsyncRequest;

class Request implements IRequest
{
	/** @var string */
	protected $url;

	/** @var resource cURL handler */
	protected $handle;

	/**
	 * @param string $url The URL to fetch.
	 */
	public function __construct($url)
	{
		$this->url = $url;
		$this->handle = curl_init();
		if ($this->handle) {
			curl_setopt_array($this->handle, [
				CURLOPT_URL => $url,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_HEADER => true,
				CURLOPT_FOLLOWLOCATION => true]
			);
		} else {
			throw new \RuntimeException('cURL interface is not available.');
		}
	}

	/**
	 * Sets cURL option
	 * @param int $curlOption
	 * @param mixed $value
	 */
	public function setOption($curlOption, $value)
	{
		curl_setopt($this->handle, $curlOption, $value);
	}

	/**
	 * Sets multiple cURL options
	 * @param array $curlOptions
	 */
	public function setOptionArray($curlOptions)
	{
		curl_setopt_array($this->handle, $curlOptions);
	}

	/**
	 * @internal
	 * @return resource
	 */
	public function getHandle()
	{
		return $this->handle;
	}

	/**
	 * @internal
	 * @param string $curlResponse
	 * @return Response
	 */
	public function createResponse($curlResponse)
	{
		$error = curl_error($this->handle);
		if ($error === '') {
			$error = null;
		}

		$httpCode = curl_getinfo($this->handle, CURLINFO_HTTP_CODE);

		$headerSize = curl_getinfo($this->handle, CURLINFO_HEADER_SIZE);
		$header = trim(substr($curlResponse, 0, $headerSize));
		$headers = preg_split('~\r\n|\n|\r~', $header);

		$body = substr($curlResponse, $headerSize);

		return new Response($this->url, $error, $httpCode, $headers, $body);
	}

	/**
	 * Closes cURL resource and frees the memory.
	 */
	public function __destruct()
	{
		if (isset($this->handle)) {
			curl_close($this->handle);
		}
	}
}
