<?php require_once("../include/head.inc.php");?>
<?php require_once(SYS_ROOT_PATH."include/language.inc.php");?>
<script>whbRemoveMask();</script>

<div class="contentDIV">
<p><img src="<?php echo SYS_EXTJS_URL?>images/apple2.gif" width="16" height="16" /> <span class="titlestyle">功能描述：发私信接口</span></p>
<p class="subtitlestyle">（一）服务接口请求地址：</p>
<table width="90%" border="1" class="dbTable">
  <tr class="td_header">
    <td width="15%">字段名称</td>
      <td width="15%">请求类型</td>
    <td width="85%">字段信息</td>
    </tr>
  <tr>
    <td>请求的地址</td>
      <td>POST</td>
    <td>v1/message</td>
  </tr>
</table>
<p class="subtitlestyle">（二）POST参数列表：</p>
<table width="90%" border="1" class="dbTable">

 <?php require_once ("../include/required_or_optional.php"); ?>
 <?php require_once ("../include/token.required.php"); ?>
    <tr>
        <td>to_user_id</td>
        <td >被私信用户ID</td>
        <td >是</td>
        <td ></td>
    </tr>
    <tr>
        <td>content</td>
        <td >内容</td>
        <td >是</td>
        <td ></td>
    </tr>

</table>
<p class="subtitlestyle">（三）服务接口响应请求：</p>
<table width="90%" border="1" class="dbTable">
  <tr class="td_header">
    <td width="51%">响应结果</td>
    <td width="49%">备注</td>
  </tr>
  <?php require_once ("../include/success.inc.php"); ?>
<?php require_once("../include/error.inc.php");?>
</table>
</div>
<?php require_once("../include/foot.inc.php");?>