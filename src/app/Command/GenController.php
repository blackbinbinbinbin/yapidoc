<?php
namespace App\Command;

use Lib\ApidocSchema\SchemaParse;
use Lib\ApidocSchema\AnnotationParser;
use Lib\File\FindClassFile;
use Lib\File\OutputDump;
use ToolCli\CommandController;
use App\Constants\CliConsts;
use ToolCli\CliPrinter;

class GenController extends CommandController
{
    const OP_FILE_PATH = "filepath";
    const OP_GROUP = "group";
    const OP_OUTPUT = "output";
    const OP_DEBUG = "debug";
    const OP_COUNT = "options_count";

    private $params = [];

    private $outputer = null;

    public function run($argv)
    {
        $this->initParams($argv);
        if ($this->params[self::OP_COUNT] <= 0) {
            $this->help();
            return;
        }

        // 开始创建
        $filePath = $this->getOptions(self::OP_FILE_PATH);
        $this->getCommand()->getPrinter()->Log("filepath={$filePath}", CliPrinter::INFO);
        $files = $this->getFileLists($filePath);
        // 文件输出类
        $outputer = new OutputDump();
        if (!empty($this->getOptions(self::OP_OUTPUT))) {
            $outputer->setOutputPath($this->getOptions(self::OP_OUTPUT));
        }
        foreach ($files as $file) {
            // 生成 swagger api 文档
            $apiSchema = $this->classFileGenSwaggerApiDoc($file);
            $outputer->outputApiDoc($apiSchema);
        }
    }

    private function getFileLists($filePath)
    {
        if (!file_exists($filePath)) {
            $this->getCommand()->getPrinter()->Log("此文件不存在，请检查", CliPrinter::ERROR);
            return [];
        }

        $fileFinder = new FindClassFile($filePath);
        return $fileFinder->getFiles();
    }

    public function classFileGenSwaggerApiDoc($docfile)
    {
        $phpCode = file_get_contents($docfile);
        $pattern = '/\/\*\*([\s\S]*?)\*\/\s*(?:public|private|protected|static)?\s*function\s+([^\(]+)\s*\([^)]*\)\s*\{/s';

        $clssPattern = '/class\s+([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)/';
        // 执行正则匹配
        $className = "";
        if (preg_match($clssPattern, $phpCode, $matches)) {
            $className = $matches[1];
            // 输出类名
            if ($this->params[self::OP_DEBUG]) {
                $this->getCommand()->getPrinter()->Log("找到类名：class={$className}", CliPrinter::WARNING);
            }
        } else {
            $this->getCommand()->getPrinter()->Log("{$docfile} 没有找到类名 class", CliPrinter::WARNING);
            return;
        }

        $schemaParser = new SchemaParse();
        if (preg_match_all($pattern, $phpCode, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $docComment = $match[1];
                $methodName = $match[2];

                if (empty($docComment)) {
                    continue;
                }
                if (empty(AnnotationParser::filterDoc($docComment))) {
                    continue;
                }

                if ($docComment !== false) {
                    // 处理 Apidoc 文档
                    $parsedAnnotationsData = AnnotationParser::parse($docComment);
                    $baseInfo = $parsedAnnotationsData["base_info"];
                    if (empty($baseInfo)) {
                        $this->getCommand()->getPrinter()->Log("此接口：{$methodName} 解析 yapidoc 内容为空", CliPrinter::INFO);
                        continue;
                    }

                    // api请求
                    $urlPath = $baseInfo["path"];
                    // api接口方法
                    $apiMethod = $baseInfo["method"];
                    // 接口分类
                    $tags = $baseInfo["tags"];
                    // 接口名称
                    $summary = $baseInfo["summary"];

                    $this->getCommand()->getPrinter()->Log("解析接口：[{$apiMethod}]$urlPath - [{$tags}]{$summary} ，开始解析 yapidoc 内容...", CliPrinter::INFO);

                    $schemaParser->buildSwaggerApiDoc($parsedAnnotationsData);
                }
            }
        }
        $result = $schemaParser->getSwaggerApiDoc();
        if (empty($result)) {
            $result = [];
        }
        return $result;
    }

    private function getOptions($optionName)
    {
        return $this->params[$optionName] ?? null;
    }

    public function help()
    {
        $this->getCommand()->getPrinter()->Display(CliConsts::ASCII_YAPIDOC_TITLE, CliPrinter::INFO);
        $helpText = <<<HELP
Usage: yapidoc gen [options]

command:
  --filepath    Parse controller file path. If the path is a directory, recursively scan all files in that directory
                example：    
                    --filepath=/path/from/to/file.Controller.php
  --output      Custom output file path
                example:
                    --output=/path/from/to/doc.yapidoc.php
  --group       The interface grouping of yapi doc is equivalent to the "tags" classification in swaager   
                example：
                    --group=tag_name    
  --debug       Turn on the debugging switch to print more detailed error information, making it easier for developers to debug
                example:
                    --debug=true
              
Description:
  This is a command that can generate Yapidoc
  
HELP;
        $this->getCommand()->getPrinter()->Display($helpText, CliPrinter::INFO);
    }

    public function initParams($arguments)
    {
        $params = [
            self::OP_DEBUG => false,
            self::OP_FILE_PATH => "",
            self::OP_OUTPUT => "",
            self::OP_GROUP => null,
            self::OP_COUNT => 0
        ];

        foreach ($arguments as $arg) {
            if (strpos($arg, '--') === 0) {
                $params[self::OP_COUNT]++;
                if (strpos($arg, '=') !== false) {
                    list($name, $value) = explode('=', substr($arg, 2), 2);
                    $params[$name] = $value;
                } else {
                    $name = substr($arg, strpos($arg, '--')+2);
                    $params[$name] = true;
                }
            }
        }
        $this->params = $params;
    }

}