<?php
namespace Lib\ApidocSchema;

use ToolCli\CliPrinter;

class SchemaParse
{
    protected $printer;
    private $swaggerApi = [];
    private $exampleJsonString;
    public function __construct($title = "", $description = "")
    {
        $this->printer = new CliPrinter();
        $this->initSwaggerApi($title, $description);
    }

    /**
     * 返回生成的 swagger api 形式的接口文档 json
     * @return array
     * Author: xubin2<xubin2@37.com>
     */
    public function getSwaggerApiDoc()
    {
        return $this->swaggerApi;
    }

    /**
     * 根据注解格式化后的数据，构建成符合 swagger api 规范的 json
     * @param $parsedAnnotationsData
     * @return array
     */
    public function buildSwaggerApiDoc($parsedAnnotationsData)
    {
        $baseInfo = $parsedAnnotationsData["base_info"];
        if (empty($baseInfo)) {
            return [false, "此基础信息为空"];
        }

        // api请求
        $urlPath = $baseInfo["path"];
        // api接口方法
        $method = $baseInfo["method"];
        // 接口分类
        $tags = $baseInfo["tags"];
        // 接口名称
        $summary = $baseInfo["summary"];
        // 接口描述
        $description = $baseInfo["description"];

        // 添加接口分类
        if (!in_array($tags, $this->swaggerApi["tags"])) {
            $this->swaggerApi["tags"][] = $tags;
        }

        // parameters 参数处理
        $annotationsParameters = $parsedAnnotationsData["@Parameters"];
        $parameters = [];
        if (!empty($annotationsParameters)) {
            $parameters = $this->getParameters($annotationsParameters);
        }

        // requestBody 请求处理
        $annotationsRequestBody = $parsedAnnotationsData["@RequestBody"];
        $requestBody = [];
        if (!empty($annotationsRequestBody)) {
            $requestBody = $this->getRequestBody($annotationsRequestBody);
        }

        // responses 响应处理
        $annotationsResponses = $parsedAnnotationsData["@Responses"];
        $responses = [];
        if (!empty($annotationsResponses)) {
            $responses = $this->getResponses($annotationsResponses);
        }

        $pathsItem = [];
        $pathsItem[$urlPath] = [
            $method => [
                "summary" => $summary,
                "x-apifox-folder" => $tags,
                "x-apifox-status" => "released",
                "deprecated" => false,
                "description" => $description,
                "tags" => [$tags],
                "parameters" => $parameters,
                "requestBody" => $requestBody,
                "responses" => $responses,
            ]
        ];

        if (empty($this->swaggerApi)) {
            $this->initSwaggerApi();
        }

        foreach ($pathsItem as $pathName => $item) {
            $this->swaggerApi["paths"][$pathName] = $item;
        }

        return [true, "path接口构造成功"];
    }

    private function getPrinter()
    {
        return $this->printer;
    }

    /**
     * 判断是否是关联数组
     * @param $arr
     * @return bool
     * Author: xubin2<xubin2@37.com>
     */
    private static function isAssociativeArray($arr) {
        return array_values($arr) !== $arr;
    }

    private function initSwaggerApi($title = "", $description = "")
    {
        $swaggerApi = self::getDefaultSwaggerApi($title, $description);
        $this->swaggerApi = $swaggerApi;
    }

    public static function getDefaultSwaggerApi($title = "", $description = "") {
        return [
            "openapi" => "3.0.1",
            "info" => [
                "title" => $title,
                "description" => $description,
                "version" => "1.0.0"
            ],
            "tags" => [],
            "paths" => [],
        ];
    }

    private function generateSchemaFromExpStr($exampleJsonString)
    {
        if (!is_array($exampleJsonString)) {
            // 需要保存一下 exampleJsonString
            $this->exampleJsonString = $exampleJsonString;
            $exampleData = json_decode($exampleJsonString, true);
            if ($exampleData === null && json_last_error() !== JSON_ERROR_NONE) {
                $errorMessage = json_last_error_msg();
                $this->getPrinter()->Log("JSON 解析失败: $errorMessage\n", CliPrinter::ERROR);
                return [];
            }
        } else {
            $exampleData = $exampleJsonString;
        }

        $properties = [
            "type" => "",
            "x-apifox-orders" => [],
            "x-apifox-ignore-properties" => [],
            "x-apifox-folder" => "Schemas"
        ];
        if (empty($exampleData)) {
            return $properties;
        }
        if (!self::isAssociativeArray($exampleData)) {
            $properties["type"] = "array";
            $properties["items"] = [];
        } else {
            $properties["type"] = "object";
            $properties["properties"] = [];
        }

        if ($properties["type"] == "array") {
            // 判断子项是否是object
            $val = array_pop($exampleData);
            if (is_null($val)) {
                $val = "null";
            }

            if (is_numeric($val)) {
                $items = ["type" => "integer", "description" => ""];
            } else if (is_string($val)) {
                $items = ["type" => "string", "description" => ""];
            } else {
                $vIterm = [];
                foreach ($val as $vv) {
                    $vIterm = $vv;
                }

                if (!self::isAssociativeArray($val) && (is_string($vIterm) || is_integer($vIterm))) {
                    if (is_numeric($vIterm)) {
                        $items = ["type" => "integer", "description" => ""];
                    } else {
                        $items = ["type" => "string", "description" => ""];
                    }
                } else {
                    $itemsRet = $this->generateSchemaFromExpStr($val);

                    $itemsType = "array";
                    if (self::isAssociativeArray($val)) {
                        $itemsType = "object";
                    }
                    $items = ["type" => $itemsType, "description" => ""];
                    if ($itemsType == "array") {
                        $items["items"] = $itemsRet["items"];
                    } else {
                        $items["properties"] = [];
                        if (isset($itemsRet["properties"])) {
                            $items["properties"] = $itemsRet["properties"];
                        }
                        $items["x-apifox-orders"] = [];
                        if (isset($itemsRet["x-apifox-orders"])) {
                            $items["x-apifox-orders"] = $itemsRet["x-apifox-orders"];
                        }
                        $items["x-apifox-ignore-properties"] = [];
                        $items["x-apifox-folder"] = "Schemas";
                    }
                }
            }
            $properties["items"] = $items;
        } else {
            $fields = [];
            $propertiesData = [];
            foreach ($exampleData as $k => $val) {
                $fields[] = $k;
                if (is_null($val)) {
                    $val = "null";
                }
                if (is_numeric($val)) {
                    $items = ["type" => "integer", "description" => ""];
                } else if (is_string($val)) {
                    $items = ["type" => "string", "description" => ""];
                } else {
                    $vIterm = [];
                    foreach ($val as $vv) {
                        $vIterm = $vv;
                        break;
                    }

                    if (!self::isAssociativeArray($val) && (is_string($vIterm) || is_integer($vIterm))) {
                        $items = ["type" => "array", "description" => ""];

                        if (is_numeric($vIterm)) {
                            $valType = "integer";
                        } else {
                            $valType = "string";
                        }
                        $items["items"] = ["type" => $valType, "description" => ""];
                    } else {

                        $itemsRet = $this->generateSchemaFromExpStr($val);
                        $keyType = "";
                        if (!empty($k) && !empty($this->exampleJsonString)) {
                            if (preg_match('/"'.$k.'"\s*:\s*{\s*/', $this->exampleJsonString)) {
                                $keyType = "object";
                            } elseif (preg_match('/"'.$k.'"\s*:\s*\[\s*/', $this->exampleJsonString)) {
                                $keyType = "array";
                            } else {
                                $keyType = "null";
                            }
                        }

                        $items = ["type" => $keyType, "description" => ""];
                        if ($keyType == "array") {
                            $items["items"] = $itemsRet["items"];
                        } else if ($keyType == "object") {
                            $items["properties"] = [];
                            if (isset($itemsRet["properties"])) {
                                $items["properties"] = $itemsRet["properties"];
                            }
                            $items["x-apifox-orders"] = [];
                            if (isset($itemsRet["x-apifox-orders"])) {
                                $items["x-apifox-orders"] = $itemsRet["x-apifox-orders"];
                            }
                            $items["x-apifox-ignore-properties"] = [];
                            $items["x-apifox-folder"] = "Schemas";
                        } else {
                            $propertiesData[$k] = $itemsRet;
                        }
                    }
                }
                $propertiesData[$k] = $items;
            }
            $properties["x-apifox-orders"] = $fields;
            $properties["properties"] = $propertiesData;
        }

        return $properties;
    }

    private function getParameters($parametersDatas)
    {
        if (empty($parametersDatas)) {
            return [];
        }
        $parameters = [];
        foreach ($parametersDatas as $param) {
            if (empty($param)) {
                continue;
            }
            $parameters[] = [
                "name" => $param["name"],
                "in" => $param["in"],
                "description" => $param["description"],
                "required" => $param["required"] == "required" ? true : false,
                "type" => $param["type"],
                "format" => $param["type"]
            ];
        }

        return $parameters;
    }

    private function getRequestBody($requestBodyDatas)
    {
        if (empty($requestBodyDatas)) {
            return [];
        }

        $request = array_pop($requestBodyDatas);
        $exampleJsonStr = $request["example"];
        if (empty($exampleJsonStr)) {
            return [];
        }

        $schemaData = $this->generateSchemaFromExpStr($exampleJsonStr);
        $this->exampleJsonString = "";

        $requestBody = [
            "description" => "",
            "required" => true,
            "content" => [
                "application/json" => [
                    "schema" => $schemaData,
                ],
            ],
        ];

        return $requestBody;
    }

    private function getResponses($responsesDatas)
    {
        $defaultResponse = [
            "200" => [
                "description" => "成功",
                "content" => [
                    "application/json" => [
                        "schema" => [
                            "properties" => [
                                "code" => [
                                    "description" => "状态码",
                                    "type" => "integer"
                                ],
                                "msg" => [
                                    "description" => "返回信息",
                                    "type" => "string",
                                ],
                                "data" => [
                                    "properties" => [],
                                    "type" => "object",
                                    "x-apifox-orders" => [],
                                    "x-apifox-ignore-properties" => [],
                                    "x-apifox-folder" => "Schemas"
                                ]
                            ],
                            "type" => "object",
                            "x-apifox-orders" => [
                                "code",
                                "msg",
                                "data"
                            ],
                            "x-apifox-ignore-properties" => [],
                            "x-apifox-folder" => "Schemas"
                        ]
                    ]
                ]
            ]
        ];
        if (empty($responsesDatas)) {
            return $defaultResponse;
        }

        $responses = array_pop($responsesDatas);
        $exampleJsonStr = $responses["example"];
        if (empty($exampleJsonStr)) {
            return $defaultResponse;
        }

        $schemaData = $this->generateSchemaFromExpStr($exampleJsonStr);
        $this->exampleJsonString = "";
        if (empty($schemaData)) {
            return $defaultResponse;
        }

        $defaultResponse["200"]["content"]["application/json"]["schema"] = $schemaData;
        return $defaultResponse;
    }
}