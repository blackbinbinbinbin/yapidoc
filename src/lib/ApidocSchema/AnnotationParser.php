<?php
namespace Lib\ApidocSchema;

class AnnotationParser
{
    public static $parseKeywords = [
        'parameters' => "@Parameters",
        'requestBody' => "@RequestBody",
        'responses' => "@Responses",
    ];

    /**
     * 过滤出doc注释文档内容
     * @param $phpFuncDoc
     * @return string
     * Author: xubin2<xubin2@37.com>
     */
    public static function filterDoc($phpFuncDoc)
    {
        if (strpos($phpFuncDoc, "@Yapidoc") !== false) {
            return $phpFuncDoc;
        } else {
            return "";
        }
    }

    /**
     * 预处理注释，主要处理关键字数组中对应的数据
     * @param $comment
     * Author: xubin2<xubin2@37.com>
     */
    public static function preParse($comment)
    {
        $preParsedResult = [];
        foreach (self::$parseKeywords as $keyword) {
            list($k, $parsedData, $parsedComment) = self::parseKeywordComment($keyword, $comment);
            $preParsedResult[$k] = $parsedData;
            $comment = $parsedComment;
        }

        return [$preParsedResult, $comment];
    }

    /**
     * 解析关键词数据
     * @param $keyword
     * @param $apidocComments
     * @return array
     * Author: xubin2<xubin2@37.com>
     */
    public static function parseKeywordComment($keyword, $apidocComments)
    {
        $matches = [];
        preg_match_all('/'.$keyword.'=\((.*?)\)/s', $apidocComments, $matches);
        $sourceComments = $apidocComments;
        $apidocComments = $matches[1];
        $parsedComments = [];

        foreach ($apidocComments as $comment) {
            $comment = str_replace("*", "", $comment);
            $lines = explode(",\n", $comment);

            $parsedComment = [];
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) {
                    continue;
                }

                $keyval = explode('=', $line);
                if (count($keyval) != 2) {
                    if (strpos($line, '=') !== false) {
                        $keyval = [];
                        $index = strpos($line, '=');
                        $keyval[0] = substr($line, 0, $index);
                        $keyval[1] = substr($line, $index+1);
                    } else {
                        echo "无法解析：".$line."\n";
                    }
                }
                $parsedComment[$keyval[0]] = trim($keyval[1], '"');
            }
            $parsedComments[] = $parsedComment;
        }
        // 把 @{keyword} 块给替换去除掉
        $sourceComments = preg_replace('/'.$keyword.'=\((.*?)\)(,?)/s', '', $sourceComments, count($apidocComments));

        return [$keyword, $parsedComments, $sourceComments];
    }

    /**
     * 处理解析流程
     * @param $phpCode
     * @return array
     * Author: xubin2<xubin2@37.com>
     */
    public static function parse($phpCode)
    {
        $matches = [];
        preg_match_all('/@Yapidoc\((.*)\)/s', $phpCode, $matches);
        $apidocComments = $matches[1];

        $parseResult = [];

        // 预处理
        foreach ($apidocComments as $k => $comment) {
            // 处理入参 params
            list($paramDatas, $content) = self::preParse($comment);
            // 处理过后的注释文本
            $apidocComments[$k] = $content;
            foreach ($paramDatas as $key => $data) {
                $parseResult[$key] = $data;
            }
        }


        $parsedComments = [];
        foreach ($apidocComments as $comment) {
            $comment = str_replace("*", "", $comment);
            $lines = explode(',', $comment);

            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) {
                    continue;
                }

                $keyval = explode('=', $line);
                if (count($keyval) != 2) {
                    if (strpos($line, '=') !== false) {
                        $keyval = [];
                        $index = strpos($line, '=');
                        $keyval[0] = substr($line, 0, $index);
                        $keyval[1] = substr($line, $index+1);
                    } else {
                        echo "无法解析：".$line."\n";
                    }
                }
                $parsedComments[$keyval[0]] = str_replace('"', '', $keyval[1]);
            }
        }
        $parseResult['base_info'] = $parsedComments;

        return $parseResult;
    }
}
