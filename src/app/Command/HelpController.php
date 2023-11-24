<?php

namespace App\Command;
use ToolCli\CommandController;
use App\Constants\CliConsts;
use ToolCli\CliPrinter;

class HelpController extends CommandController
{
    private $helpText = <<<HELP
Usage: yapidoc [command] [options]

command:
  help    Show this help message and exit.
  gen     Parse controller file path to generate Yapidoc
  
Description:
  This is a script that can help developers automatically generate Yapidoc documents, and using annotations can easily generate Yapidoc. For examples of using scripts, please refer to/example in the same level directory of this script. For details, please refer to the user manual document: http://
  
HELP;
    public function run($argv)
    {
        $this->getCommand()->getPrinter()->Display(CliConsts::ASCII_YAPIDOC_TITLE, CliPrinter::INFO);
        $this->getCommand()->getPrinter()->Display($this->helpText, CliPrinter::INFO);
    }
}