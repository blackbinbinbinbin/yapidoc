<?php
namespace ToolCli;
class Command
{
    protected $printer;

    /**
     * @var CommandRegistry
     */
    protected $commandRegistry;

    public function __construct()
    {
        $this->printer = new CliPrinter();
        $this->commandRegistry = new CommandRegistry();
    }

    public function getPrinter()
    {
        return $this->printer;
    }

    public function registerController(array $names, CommandController $controller)
    {
        foreach ($names as $name) {
            $this->commandRegistry->registerController($name, $controller);
        }
    }

    public function registerCommand(array $names, $callable)
    {
        foreach ($names as $name) {
            $this->commandRegistry->registerCommand($name, $callable);
        }
    }

    public function getCommand($command)
    {
        if (isset($this->registry[$command])) {
            return $this->registry[$command];
        } else {
            return null;
        }
    }

    public function run(array $argv = [], $defaultCommand = 'help')
    {
        $commandName = $defaultCommand;

        if (isset($argv[1])) {
            $commandName = $argv[1];
        }

        try {
            call_user_func($this->commandRegistry->getCallable($commandName), $argv);
        } catch (\Exception $e) {
            $this->getPrinter()->Display("ERROR: " . $e->getMessage(), CliPrinter::ERROR);
            exit;
        }
    }
}