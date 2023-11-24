<?php
namespace Lib\File;

use ToolCli\CliPrinter;

class OutputDump
{
    private $swaggerApi;
    private $outputFilePath;

    public function  __construct()
    {
        // 设定默认的输出文件路径
        $tmpPath = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . "tmp" . DIRECTORY_SEPARATOR . date("Y-m-d");
        if (!is_dir($tmpPath)) {
            mkdir($tmpPath);
        }
        $now = time();
        $this->outputFilePath = $tmpPath . DIRECTORY_SEPARATOR . "tmp_{$now}.yapidoc.json";
    }

    public function setOutputPath($filePath)
    {
        if (is_dir($filePath)) {
            $now = time();
            $filePath .= DIRECTORY_SEPARATOR . "tmp_{$now}.yapidoc.json";
        }
        $this->outputFilePath = $filePath;
    }

    public function outputApiDoc($swaggerApiSchema)
    {
        $jsonContent = json_encode($swaggerApiSchema, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);

        $outputJsonFilePath = $this->outputFilePath;
        $originJsonContent = "";
        if (!file_exists($outputJsonFilePath)) {
            touch($outputJsonFilePath);
        } else {
            $originJsonContent = file_get_contents($outputJsonFilePath);
        }

        // 这里需要智能合并一下
        if (!empty($originJsonContent)) {
            $originYapiDoc = json_decode($originJsonContent, true);
            $this->swaggerApi = self::mergeYapidoc($originYapiDoc, $swaggerApiSchema);
            $jsonContent = json_encode($this->swaggerApi, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
        }

        file_put_contents($outputJsonFilePath, $jsonContent);
        $printer = new CliPrinter();
        $printer->Log("输出 api 文件路径为：".$outputJsonFilePath, CliPrinter::INFO);
        return $outputJsonFilePath;
    }


    public function mergeYapidoc($originYapiDoc, $swaggerApiDoc)
    {
        // 处理tags
        $oldTags = [];
        if (array_key_exists("tags", $originYapiDoc)) {
            $oldTags = $originYapiDoc["tags"];
        }
        if (!empty($oldTags)) {
            if (empty($swaggerApiDoc["tags"])) {
                $swaggerApiDoc["tags"] = [];
            }
            foreach ($oldTags as $tag) {
                if (!in_array($tag, $swaggerApiDoc["tags"])) {
                    $swaggerApiDoc["tags"][] = $tag;
                }
            }
        }

        // 处理 info.title 和 info.description
        $oldInfoTitle = "";
        $oldInfoDesc = "";
        if (array_key_exists("info", $originYapiDoc)) {
            $oldInfo = $originYapiDoc["info"];
            $oldInfoTitle = $oldInfo["title"];
            $oldInfoDesc = $oldInfo["description"];
        }
        if (!empty($oldInfoTitle) && empty($swaggerApiDoc["info"]["title"])) {
            $swaggerApiDoc["info"]["title"] = $oldInfoTitle;
        }
        if (!empty($oldInfoDesc) && empty($swaggerApiDoc["info"]["description"])) {
            $swaggerApiDoc["info"]["description"] = $oldInfoDesc;
        }

        // 合并相关描述和旧接口
        $oldApiPathData = [];   // 如果是原先已存在的，但是在新添加的 $swaggerApiDoc 中不存在的接口
        foreach ($originYapiDoc["paths"] as $path => $datas) {
            if (!array_key_exists($path, $swaggerApiDoc["paths"])) {
                $oldApiPathData[$path] = $datas;
            }

            foreach ($datas as $method => $data) {
                // 合并旧接口文档中关于字段的描述 requestBody responses
                $mergeDataKey = ["requestBody", "responses"];

                foreach ($mergeDataKey as $dataKey) {
                    if (empty($data[$dataKey])) {
                        continue;
                    }
                    if ($dataKey == "responses") {
                        $dataSchema = $data[$dataKey]["200"]["content"]["application/json"]["schema"];
                    } else {
                        $dataSchema = $data[$dataKey]["content"]["application/json"]["schema"];
                    }
                    $descMap = [];
                    $descMap = self::getDescMap("", $descMap, $dataSchema);
                    //替换
                    if (!empty($descMap) && isset($swaggerApiDoc["paths"][$path][$method][$dataKey]["content"]["application/json"]["schema"])) {
                        $swaggerReqSchema = &$swaggerApiDoc["paths"][$path][$method][$dataKey]["content"]["application/json"]["schema"];
                        foreach ($descMap as $keyPath => $val) {
                            self::setNestedArrayValue($swaggerReqSchema, $keyPath, $val);
                        }
                    }
                }
            }
        }
        foreach ($oldApiPathData as $oldPath => $oldApi) {
            $swaggerApiDoc["paths"][$oldPath] = $oldApi;
        }

        return $swaggerApiDoc;
    }

    private static function getDescMap($preKey, &$descMap, $apiDocSchema)
    {
        $key = "";
        if (isset($apiDocSchema["properties"])) {
            $key = "properties";
        }
        if (isset($apiDocSchema["items"])) {
            $key = "items";
        }

        if (empty($key)) {
            return $descMap;
        }
        if (!empty($preKey)) {
            $key = $preKey .".".$key;
        }

        if (isset($apiDocSchema["properties"])) {
            foreach ($apiDocSchema["properties"] as $k => $data) {
                $nextkey = "{$key}.{$k}";

                if (isset($data["description"]) && !empty($data["description"])) {
                    $mapkey = "{$nextkey}.description";
                    $descMap[$mapkey] = $data["description"];
                }

                if (isset($data["type"]) && ($data["type"] == "object" || $data["type"] == "array")) {
                    self::getDescMap($nextkey, $descMap, $data);
                }
            }
        }

        if (isset($apiDocSchema["items"])) {
            foreach ($apiDocSchema["items"] as $k => $data) {
                $nextkey = "{$key}.{$k}";

                if (!empty($data["description"])) {
                    $mapkey = "{$nextkey}.description";
                    $descMap[$mapkey] = $data["description"];
                }

                if (isset($data["type"]) && $data["type"] == "object" || isset($data["type"]) && $data["type"] == "array") {
                    self::getDescMap($nextkey, $descMap, $data);
                }
            }
        }

        return $descMap;
    }

    private static function  setNestedArrayValue(&$array, $keys, $value) {
        $keys = explode('.', $keys);
        $temp = &$array;

        foreach ($keys as $key) {
            if (!isset($temp[$key])) {
                return;
            }
            $temp = &$temp[$key];
        }

        $temp = $value;
    }
}