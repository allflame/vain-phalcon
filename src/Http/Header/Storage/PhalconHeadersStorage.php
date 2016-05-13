<?php
/**
 * Created by PhpStorm.
 * User: allflame
 * Date: 5/12/16
 * Time: 12:58 PM
 */

namespace Vain\Phalcon\Http\Header\Storage;

use Phalcon\Http\Response\HeadersInterface as PhalconHeadersInterface;
use Vain\Http\Header\Storage\AbstractHeaderStorage;
use Vain\Phalcon\Exception\UnsupportedStorageCallException;
use Vain\Phalcon\Http\Header\Factory\PhalconHeaderFactory;

class PhalconHeadersStorage extends AbstractHeaderStorage implements PhalconHeadersInterface
{
    /**
     * @inheritDoc
     */
    public function set($name, $value)
    {
        return $this->createHeader($name, $value);
    }

    /**
     * @inheritDoc
     */
    public function get($name)
    {
        if (null === ($header = $this->getHeader($name))) {
            return false;
        }

        return implode(', ', $header->getValues());
    }

    /**
     * @inheritDoc
     */
    public function setRaw($header)
    {
        throw new UnsupportedStorageCallException($this, __METHOD__);
    }

    /**
     * @inheritDoc
     */
    public function send()
    {
        throw new UnsupportedStorageCallException($this, __METHOD__);
    }

    /**
     * @inheritDoc
     */
    public function reset()
    {
        return $this->resetHeaders();
    }

    /**
     * @inheritDoc
     */
    public static function __set_state($data)
    {
        $instance = new self(new PhalconHeaderFactory());
        if (false === array_key_exists('_headers', $data)) {
            foreach ($data['_headers'] as $name => $value) {
                $instance->set($name, $value);
            }
        }

        return $instance;
    }
}