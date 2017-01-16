<?php
/**
 * Class ArgumentParser | ArgumentParser.php
 * @package ORM\Console
 * @author  Florian Knapp <office@florianknapp.de>
 */
namespace ORM\Console;

/**
 * Class ArgumentParser
 */
class ArgumentParser
{

    /** @var array */
    protected $arguments = [];

    /**
     * ArgumentParser constructor.
     * @param array $argv
     */
    public function __construct($argv)
    {
        $this->parseInput($argv);
    }

    /**
     * @param array $argv
     * @return ConsoleInterface
     * @throws \Exception
     */
    protected function parseInput(array $argv)
    {
        $args = array_splice($argv, 1, count($argv));

        for ($i = 0; $i < count($args); $i++) {

            if (strpos($args[$i], '-') === false) {
                continue;
            }

            $this->set(str_replace('-', '', $args[$i]), $args[$i+1]);

        }

        if (strpos($args[0], ':') !== false) {

            $parts  = explode(':', $args[0]);
            $class  = $parts[0];
            $method = $parts[1] . 'Action';
            $ns     = '\ORM\Console\Handler\\' . ucfirst($class);

            return call_user_func([new $ns(), $method], $this);

        }

        throw new \Exception('No matching handler found');

    }

    /**
     * @param string $arg
     * @param string $value
     */
    public function set($arg, $value)
    {
        $this->arguments[$arg] = $value;
    }

    /**
     * @param string $arg
     * @return string
     */
    public function get($arg)
    {
        if (empty($this->arguments[$arg])) {
            return '';
        }

        return $this->arguments[$arg];
    }

}