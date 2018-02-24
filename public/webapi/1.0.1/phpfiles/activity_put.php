<?php require_once("../include/head.inc.php");?>
<?php require_once(SYS_ROOT_PATH."include/language.inc.php");?>
<script>whbRemoveMask();</script>

<div class="contentDIV">
<p><img src="<?php echo SYS_EXTJS_URL?>images/apple2.gif" width="16" height="16" /> <span class="titlestyle">功能描述：编辑活动接口</span></p>
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
    <td>v2/activity</td>
  </tr>
</table>
<p class="subtitlestyle">（二）PUT参数列表：</p>
<table width="90%" border="1" class="dbTable">

 <?php require_once ("../include/required_or_optional.php"); ?>
 <?php require_once ("../include/token.required.php"); ?>
    <tr>
        <td>uuid</td>
        <td >活动ID</td>
        <td >是</td>
        <td ></td>
    </tr>
 <tr>
   <td>name</td>
   <td>名称</td>
   <td>是</td>
   <td></td>
 </tr>
  <tr>
   <td>tel</td>
   <td>联系方式</td>
   <td>是</td>
   <td></td>
 </tr>
 <tr>
     <td>cover_image</td>
     <td>封面图uri</td>
     <td>&nbsp;是</td>
     <td>&nbsp;</td>
 </tr>
    <tr>
        <td>fee</td>
        <td>费用（元）</td>
        <td>&nbsp;是</td>
        <td>&nbsp;</td>
    </tr>
    <tr>
        <td>content</td>
        <td>内容</td>
        <td>&nbsp;是</td>
        <td>&nbsp;</td>
    </tr>
    <tr>
        <td>end_time</td>
        <td>截止日期</td>
        <td>&nbsp;是</td>
        <td>&nbsp;格式如：Y-m-d H:i:s</td>
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