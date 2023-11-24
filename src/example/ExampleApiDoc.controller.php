<?php

class ExampleApiDoc
{
    public $pageResUtils;

    public function __construct()
    {
    }

    /**
     * @Yapidoc(
     *  path="/index.php?c=ExampleApidoc&a=function_api_default",
     *  method="GET",
     *  operationId="getUsers",
     *  tags="apidoc文档示例",
     *  summary="默认api文档",
     *  description="默认api文档示例",
     *  @Parameters=(
     *    name="param1",
     *    in="query",
     *    description="参数1",
     *    required="required",
     *    type="integer"
     *  ),
     * @Parameters=(
     *    name="param2",
     *    in="query",
     *    description="参数2",
     *    required="required",
     *    type="integer"
     *  ),
     * @RequestBody=(
     *    example="{"package_pack_file":"/www/37games/zeus/dev_public_pageRes/zeus.ujoy.com/cache/package11.zip","attr_select":{"version":[{"big_version":["xxx"]}],"special_timenode":["anniversary"],"area":["european_american"],"brightness":["bright"],"game_type":["card"],"game_style":["q_version","ink_painting"],"scene_info":["plant"],"race":["humanity"],"posture":["kneel"],"arms":["sword"],"play_method":["big_world"],"camera_perspective":["first_person"],"designer":["liuwenjie"],"situation":["expression"],"layout":["horizontal_plate"]},"package_name":"测试素材包添加666","game_id":"1317163","lang":"en","package_res_type":"transfer_page"}"
     *  ),
     * @Responses=(
     *    example=""
     *  )
     * )
     * Author: xubin2<xubin2@37.com>
     */
    public function _function_api_default()
    {

    }
}