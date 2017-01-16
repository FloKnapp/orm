<?php
/**
 * Class Generate | Generate.php
 * @package ORM\Console
 * @author  Florian Knapp <office@florianknapp.de>
 */
namespace ORM\Console\Handler;

use ORM\Console\ArgumentParser;
use ORM\Console\ConsoleInterface;

/**
 * Class Generate
 */
class Generate implements ConsoleInterface
{

    /**
     * @param ArgumentParser $args
     */
    public function schemeAction(ArgumentParser $args)
    {
        var_dump($args->get('a'));
    }

}