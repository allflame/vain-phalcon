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

namespace Vain\Phalcon\Database\Factory;

use Vain\Connection\ConnectionInterface;
use Vain\Database\Factory\AbstractDatabaseFactory;
use Vain\Database\Generator\Factory\GeneratorFactoryInterface;
use Vain\Pdo\Connection\PdoConnectionInterface;
use Vain\Phalcon\Database\PhalconMysqlAdapter;
use Vain\Phalcon\Database\PhalconPostgresqlAdapter;
use Vain\Phalcon\Exception\UnknownPhalconDriverException;

/**
 * Class PhalconDatabaseFactory
 *
 * @author Taras P. Girnyk <taras.p.gyrnik@gmail.com>
 */
class PhalconDatabaseFactory extends AbstractDatabaseFactory
{
    private $generatorFactory;

    /**
     * PhalconDatabaseFactory constructor.
     *
     * @param string                    $name
     * @param GeneratorFactoryInterface $generatorFactory
     */
    public function __construct($name, GeneratorFactoryInterface $generatorFactory)
    {
        $this->generatorFactory = $generatorFactory;
        parent::__construct($name);
    }

    /**
     * @inheritDoc
     */
    public function createDatabase(array $configData, ConnectionInterface $connection)
    {
        /**
         * @var PdoConnectionInterface $connection
         */
        $driver = $configData['type'];
        switch ($driver) {
            case 'pgsql':
                return new PhalconPostgresqlAdapter($this->generatorFactory, $connection);
                break;
            case 'mysql':
                return new PhalconMysqlAdapter($this->generatorFactory, $connection);
                break;
            default:
                throw new UnknownPhalconDriverException($this, $driver);
        }
    }
}