<?php
/**
 * Created by PhpStorm.
 * User: allflame
 * Date: 5/20/16
 * Time: 11:48 AM
 */
namespace Vain\Phalcon\Di\Factory;

use \Phalcon\DiInterface as PhalconDiInterface;

/**
 * Interface DiFactoryInterface
 *
 * @author Taras P. Girnyk <taras.p.gyrnik@gmail.com>
 */
interface DiFactoryInterface
{
    /**
     * @param string $applicationEnv
     * @param bool   $cachingEnabled
     *
     * @return PhalconDiInterface
     */
    public function createDi($applicationEnv, $cachingEnabled);
}