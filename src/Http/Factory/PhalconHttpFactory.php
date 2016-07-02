<?php
/**
 * Vain Framework
 *
 * PHP Version 7
 *
 * @package   vain-http
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/allflame/vain-http
 */
namespace Vain\Phalcon\Http\Factory;

use Phalcon\FilterInterface as PhalconFilterInterface;
use Psr\Http\Message\UploadedFileInterface;
use Vain\Http\Cookie\Factory\CookieFactoryInterface;
use Vain\Http\Exception\UnsupportedUriException;
use Vain\Http\File\Factory\FileFactoryInterface;
use Vain\Http\Header\Factory\HeaderFactoryInterface;
use Vain\Http\Header\Provider\HeaderProviderInterface;
use Vain\Http\Request\Factory\RequestFactoryInterface;
use Vain\Http\Response\Factory\ResponseFactoryInterface;
use Vain\Http\Stream\Factory\StreamFactoryInterface;
use Vain\Http\Uri\Factory\UriFactoryInterface;
use Vain\Phalcon\Exception\UnknownFilesException;
use Vain\Phalcon\Exception\UnknownProtocolException;
use Vain\Phalcon\Exception\UnreachableFileException;
use Vain\Phalcon\Http\Cookie\Factory\PhalconCookieFactory;
use Vain\Phalcon\Http\Cookie\Storage\PhalconCookieStorage;
use Vain\Phalcon\Http\File\PhalconFile;
use Vain\Phalcon\Http\Header\Storage\PhalconHeadersStorage;
use Vain\Phalcon\Http\Request\PhalconRequest;
use Vain\Phalcon\Http\Response\PhalconResponse;
use Vain\Phalcon\Http\Stream\PhalconStream;
use Vain\Phalcon\Http\Uri\PhalconUri;

/**
 * Class PhalconHttpFactory
 *
 * @author Taras P. Girnyk <taras.p.gyrnik@gmail.com>
 */
class PhalconHttpFactory implements
    FileFactoryInterface,
    UriFactoryInterface,
    StreamFactoryInterface,
    RequestFactoryInterface,
    ResponseFactoryInterface
{
    private $filter;

    private $headerProvider;

    private $cookieFactory;

    private $headerFactory;

    /**
     * PhalconHttpFactory constructor.
     * @param PhalconFilterInterface $phalconFilter
     * @param HeaderProviderInterface $headerProvider
     * @param CookieFactoryInterface $cookieFactory
     * @param HeaderFactoryInterface $headerFactory
     */
    public function __construct(PhalconFilterInterface $phalconFilter, HeaderProviderInterface $headerProvider, CookieFactoryInterface $cookieFactory, HeaderFactoryInterface $headerFactory)
    {
        $this->filter = $phalconFilter;
        $this->headerProvider = $headerProvider;
        $this->cookieFactory = $cookieFactory;
        $this->headerFactory = $headerFactory;
    }

    /**
     * @inheritDoc
     */
    public function createFile($source, $size, $error, $fileName, $mediaType)
    {
        return new PhalconFile($this->createStream($source, 'r+'), $size, $error, $fileName, $mediaType);
    }

    /**
     * @inheritDoc
     */
    public function createStream($source, $mode)
    {
        if (false === ($resource = @fopen($source, $mode))) {
            throw new UnreachableFileException($source, $mode);
        }

        return new PhalconStream($resource);
    }

    /**
     * @param string $element
     * @param array $array
     *
     * @return string|null
     */
    protected function extractKey($element, array $array)
    {
        if (false === array_key_exists($element, $array)) {
            return null;
        }

        return $array[$element];
    }

    /**
     * @inheritDoc
     */
    public function createUri($uri)
    {
        if (false === ($explode = parse_url($uri))) {
            throw new UnsupportedUriException($this, $uri);
        }

        $extractedParts = [];
        foreach ([PHP_URL_SCHEME, PHP_URL_USER, PHP_URL_PASS, PHP_URL_HOST, PHP_URL_PORT, PHP_URL_PATH, PHP_URL_QUERY, PHP_URL_FRAGMENT] as $element) {
            $extractedParts[] = $this->extractKey($element, $explode);
        }

        return new PhalconUri(...$extractedParts);
    }


    /**
     * @param array $data
     *
     * @return array
     * @throws UnknownFilesException
     */
    protected function createFiles(array $data)
    {
        $files = [];
        foreach ($data as $key => $fileSpec) {
            switch (true) {
                case is_array($fileSpec) && array_key_exists('tmp_name', $fileSpec):
                    $files[$key] = $this->processFile($fileSpec['tmp_name'], $fileSpec['size'], $fileSpec['error'], $fileSpec['name'], $fileSpec['type']);
                    break;
                case is_array($fileSpec):
                    $files[$key] = $this->createFiles($fileSpec);
                    break;
                default:
                    throw new UnknownFilesException($this, $key);
            }
        }

        return $files;
    }

    /**
     * @param string $tmpName
     * @param int $size
     * @param int $error
     * @param string $name
     * @param string $type
     *
     * @return UploadedFileInterface[]|UploadedFileInterface
     */
    protected function processFile($tmpName, $size, $error, $name, $type)
    {
        if (false === is_array($tmpName)) {
            return $this->createFile($tmpName, $size, $error, $name, $type);
        }
        $files = [];
        foreach (array_keys($tmpName) as $tmpFileName) {
            $files[$tmpFileName] = $this->processFile($tmpFileName, $size[$tmpFileName], $error[$tmpFileName], $name[$tmpFileName], $type[$tmpFileName]);
        }

        return $files;
    }

    /**
     * @param string $protocol
     *
     * @return string mixed
     * @throws UnknownProtocolException
     */
    protected function transformProtocol($protocol)
    {
        $matches = [];
        preg_match('/HTTP\/([\d\.]*)/', $protocol, $matches);
        switch (count($matches)) {
            case 2:
                return $matches[1];
                break;
            default:
                throw new UnknownProtocolException($this, $protocol);
        }
    }

    /**
     * @inheritDoc
     */
    public function createRequest(array $serverParams, array $queryParams, array $attributes, $body, array $filesData, array $cookiesData, $streamSource)
    {
        $files = $this->createFiles($filesData);
        $cookieStorage = new PhalconCookieStorage(new PhalconCookieFactory());
        foreach ($cookiesData as $cookieName => $cookieValue) {
            $cookies[] = $cookieStorage->createCookie($cookieName, $cookieValue);
        }
        $headerStorage = new PhalconHeadersStorage($this->headerFactory);
        foreach ($this->headerProvider->getHeaders() as $headerName => $headerValue) {
            $headerStorage->createHeader($headerName, $headerValue);
        }

        return new PhalconRequest(
            $this->filter,
            $serverParams,
            $files,
            $queryParams,
            $attributes,
            $body,
            $this->transformProtocol($serverParams['SERVER_PROTOCOL']),
            $serverParams['REQUEST_METHOD'],
            $this->createUri($serverParams['REQUEST_URI']),
            $this->createStream($streamSource, 'r'),
            $cookieStorage,
            $headerStorage
        );
    }

    /**
     * @inheritDoc
     */
    public function createResponse($destinationStream, $statusCode = 200, array $headersData = [], $content = '')
    {
        $headerStorage = new PhalconHeadersStorage($this->headerFactory);
        foreach ($headersData as $headerName => $headerValue) {
            $headerStorage->createHeader($headerName, $headerValue);
        }

        $stream = $this->createStream($destinationStream, 'w+');
        $stream->write($content);

        return new PhalconResponse($statusCode, $stream, $headerStorage);
    }
}