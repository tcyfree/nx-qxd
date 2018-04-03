<?php require_once("../include/head.inc.php");?>
<?php require_once(SYS_ROOT_PATH."include/language.inc.php");?>
    <script>whbRemoveMask();</script>

    <div class="contentDIV">
        <p><img src="<?php echo SYS_EXTJS_URL?>images/apple2.gif" width="16" height="16" /> <span class="titlestyle">功能描述：获取调起小程序支付数据签名字段列表接口</span></p>
        <p class="subtitlestyle">（一）服务接口请求地址：</p>
        <table width="90%" border="1" class="dbTable">
            <tr class="td_header">
                <td width="15%">字段名称</td>
                <td width="15%">请求类型</td>
                <td width="85%">字段信息</td>
            </tr>
            <tr>
                <td>请求的地址</td>
                <td>PUT</td>
                <td>v1/wallet/order</td>
            </tr>
        </table>
        <p class="subtitlestyle">（二）参数列表：</p>
        <table width="90%" border="1" class="dbTable">
            <?php require_once ("../include/required_or_optional.php"); ?>
            <?php require_once ("../include/token.required.php"); ?>
            <tr>
                <td>id</td>
                <td >商户订单号</td>
                <td >是</td>
                <td ></td>
            </tr>

        </table>
        <p class="subtitlestyle">（三）服务接口响应请求：</p>
        <table width="90%" border="1" class="dbTable">
            <tr class="td_header">
                <td width="51%">响应结果</td>
                <td width="31%">备注</td>
            </tr>
            <tr>
                <td><p>{json信息串}</p></td>
                <td><p>详见（四）特别备注</p></td>
            </tr>
            <?php require_once ("../include/error.inc.php"); ?>
        </table>
        <p><span class="subtitlestyle">（四）特别备注</span>（infor字段说明，仅列出部分关键字段）</p>
        <table width="90%" border="1" class="dbTable">
            <tr class="td_header">
                <td width="16%">参数名称</td>
                <td width="27%">参数说明</td>
                <td width="57%">备注</td>
            </tr>
            <tr>
                <td>小程序调起支付数据签名字段列表</td>
                <td>参考字段</td>
                <td><a href="https://pay.weixin.qq.com/wiki/doc/api/wxa/wxa_api.php?chapter=7_7&index=3" target="_blank">小程序调起支付API</a> </td>
            </tr>
        </table>
        <p>&nbsp;</p>
    </div>


<?php require_once("../include/foot.inc.php");?>