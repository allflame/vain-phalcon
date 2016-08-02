<?php
/**
 * Vain Framework
 *
 * PHP Version 7
 *
 * @package   vain-phalcon
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/allflame/vain-phalcon
 */
namespace Vain\Phalcon\Api\Config\Provider;

use Vain\Api\Config\Factory\ApiConfigFactoryInterface;
use Vain\Api\Config\Provider\ApiConfigProviderInterface;
use Phalcon\Mvc\RouterInterface as PhalconMvcRouterInterface;
use Vain\Config\Data\Provider\ConfigDataProviderInterface;
use Vain\Http\Request\VainServerRequestInterface;
use Vain\Phalcon\Exception\NoModuleConfigDataException;
use Vain\Phalcon\Exception\NoRouteConfigDataException;

/**
 * Class PhalconApiConfigProvider
 *
 * @author Taras P. Girnyk <taras.p.gyrnik@gmail.com>
 */
class PhalconApiConfigProvider implements ApiConfigProviderInterface
{
    private $router;

    private $configDataProvider;

    private $configFactory;

    /**
     * PhalconApiConfigProvider constructor.
     *
     * @param PhalconMvcRouterInterface $router
     * @param ConfigDataProviderInterface $configDataProvider
     * @param ApiConfigFactoryInterface $apiConfigFactory
     */
    public function __construct(
        PhalconMvcRouterInterface $router,
        ConfigDataProviderInterface $configDataProvider,
        ApiConfigFactoryInterface $apiConfigFactory
    ) {
        $this->router = $router;
        $this->configDataProvider = $configDataProvider;
        $this->configFactory = $apiConfigFactory;
    }

    /**
     * @inheritDoc
     */
    public function getConfig(VainServerRequestInterface $request)
    {
        $moduleName = $this->router->getModuleName();
        $routeName = $this->router->getMatchedRoute()->getName();
        $configData = $this->configDataProvider->getConfigData('api');
        if (false === array_key_exists($moduleName, $configData)) {
            throw new NoModuleConfigDataException($this, $request, $moduleName);
        }
        $moduleData = $configData[$moduleName];
        if (false === array_key_exists($routeName, $moduleData)) {
            throw new NoRouteConfigDataException($this, $request, $routeName);
        }

        return $this->configFactory->createConfig($moduleData[$routeName]);
    }
}