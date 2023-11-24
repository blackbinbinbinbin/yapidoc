<?php
namespace ToolCli;

abstract class CommandController
{
    protected $command;

    abstract public function run($argv);

    public function __construct(Command $cli)
    {
        $this->command = $cli;
    }

    protected function getCommand()
    {
        return $this->command;
    }
}