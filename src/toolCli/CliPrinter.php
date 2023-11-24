<?php
namespace ToolCli;
class CliPrinter
{
    const INFO = "INFO";
    const ERROR = "ERROR";
    const WARNING = "WARNING";

    public static $colorSuccess = "\033[32m";  // 成功信息的颜色
    public static $colorError = "\033[31m";    // 错误信息的颜色
    public static $colorWarning = "\033[33m";  // 警告信息的颜色
    public static $colorReset = "\033[0m";     // 重置颜色

    public function out($message, $level = self::INFO)
    {
        $colors = [
            "INFO" => self::$colorSuccess,      // 绿色表示INFO级别
            "ERROR" => self::$colorError,       // 红色表示ERROR级别
            "WARNING" => self::$colorWarning    // 黄色表示WARNING级别
        ];

        if (isset($colors[$level])) {
            $this->output($colors[$level] . $message . self::$colorReset);
        } else {
            $this->output($message);
        }
    }

    public function output($content, $end = PHP_EOL)
    {
        echo $content . $end;
    }

    public function newline()
    {
        $this->out("", PHP_EOL);
    }

    public function Display($message, $level = "")
    {
        $this->out($message, $level);
    }

    public function Log($content, $level)
    {
        $this->out("[" . $level . "] " . $content, $level);
    }
}