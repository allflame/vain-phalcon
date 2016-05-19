<?php
/**
 * Created by PhpStorm.
 * User: allflame
 * Date: 5/10/16
 * Time: 12:42 PM
 */

namespace Vain\Phalcon\Http\Response;

use Phalcon\Http\ResponseInterface as PhalconHttpResponseInterface;
use Vain\Http\Header\Storage\HeaderStorageInterface;
use Vain\Http\Response\AbstractResponse;
use Vain\Http\Response\Emitter\EmitterInterface;
use Vain\Http\Stream\VainStreamInterface;
use Vain\Phalcon\Exception\BadRedirectCodeException;
use Vain\Phalcon\Exception\JsonErrorException;
use Vain\Phalcon\Exception\UnsupportedResponseCallException;

class PhalconResponse extends AbstractResponse implements PhalconHttpResponseInterface
{
    private $emitter;
    
    /**
     * PhalconResponse constructor.
     * @param EmitterInterface $emitter
     * @param int $code
     * @param VainStreamInterface $stream
     * @param HeaderStorageInterface $headerStorage
     */
    public function __construct(EmitterInterface $emitter, $code, VainStreamInterface $stream, HeaderStorageInterface $headerStorage)
    {
        $this->emitter = $emitter;
        parent::__construct($code, $stream, $headerStorage);
    }

    /**
     * @inheritDoc
     */
    public function setStatusCode($code, $message = null)
    {
        return $this->withStatus($code, $message);
    }

    /**
     * @inheritDoc
     */
    public function setHeader($name, $value)
    {
        $this->getHeaderStorage()->createHeader($name, $value);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setRawHeader($header)
    {
        list ($headerName, $headerValue) = explode(':', $header);
        $this->getHeaderStorage()->createHeader($headerName, $headerValue);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setExpires(\DateTime $datetime)
    {
        $cloned = clone $datetime;
        $cloned->setTimezone(new \DateTimeZone("UTC"));
        $this->getHeaderStorage()->createHeader(self::HEADER_EXPIRES, $datetime->format("D, d M Y H:i:s") . " GMT");

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setNotModified()
    {
        return $this->withStatus(304, 'Not modified');
    }

    /**
     * @inheritDoc
     */
    public function setContentType($contentType, $charset = null)
    {
        if (null === $charset) {
            $this->getHeaderStorage()->createHeader(self::HEADER_CONTENT_TYPE, $contentType);
        } else {
            $this->getHeaderStorage()->createHeader(self::HEADER_CONTENT_TYPE, sprintf('%s";charset=%s"', $contentType, $charset));
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function redirect($location = null, $externalRedirect = false, $statusCode = 302)
    {
        if ($statusCode < 300 || $statusCode > 308) {
            throw new BadRedirectCodeException($this, $statusCode);
		}

        return $this
            ->withStatus($statusCode)
            ->withHeader(self::HEADER_LOCATION, $location);
    }

    /**
     * @inheritDoc
     */
    public function setContent($content)
    {
        $this->getBody()->write($content);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setJsonContent($content)
    {
        if (false === ($encoded = json_encode($content))) {
            throw new JsonErrorException($this, $content);
        }
        
        return $this->setContent($encoded);
    }

    /**
     * @inheritDoc
     */
    public function appendContent($content)
    {
        $this->getBody()->write($content);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function send()
    {
        $this->emitter->send($this);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function sendHeaders()
    {
        return $this;
        //throw new UnsupportedResponseCallException($this, __METHOD__);
    }

    /**
     * @inheritDoc
     */
    public function sendCookies()
    {
        return $this;
        //throw new UnsupportedResponseCallException($this, __METHOD__);
    }

    /**
     * @inheritDoc
     */
    public function getContent()
    {
        return $this->getBody()->getContents();
    }

    /**
     * @inheritDoc
     */
    public function resetHeaders()
    {
        $copy = clone $this;
        $copy->getHeaderStorage()->resetHeaders();

        return $copy;
    }

    /**
     * @inheritDoc
     */
    public function setFileToSend($filePath, $attachmentName = null)
    {
        $this->getHeaderStorage()->resetHeaders();

        $basePath = $attachmentName;

        if ('string' !== gettype($attachmentName)) {
            $basePath = basename($attachmentName);
        }

        $this->getBody()->write(readfile($filePath));
        
        return $this
            ->setHeader(self::HEADER_CONTENT_DESCRIPTION, 'File Transfer')
			->setHeader(self::HEADER_CONTENT_TYPE, 'application/octet-stream')
			->setHeader(self::HEADER_CONTENT_DISPOSITION, sprintf('attachment; filename=%s', $basePath))
			->setHeader(self::HEADER_CONTENT_TRANSFER_ENCODING, 'binary');
    }
}