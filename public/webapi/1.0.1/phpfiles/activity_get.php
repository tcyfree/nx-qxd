<?php require_once("../include/head.inc.php");?>
<?php require_once(SYS_ROOT_PATH."include/language.inc.php");?>
<script>whbRemoveMask();</script>

<div class="contentDIV">
<p><img src="<?php echo SYS_EXTJS_URL?>images/apple2.gif" width="16" height="16" /> <span class="titlestyle">功能描述：活动详情接口</span></p>
<p class="subtitlestyle">（一）服务接口请求地址：</p>
<table width="90%" border="1" class="dbTable">
  <tr class="td_header">
    <td width="15%">字段名称</td>
      <td width="15%">请求类型</td>
    <td width="85%">字段信息</td>
    </tr>
  <tr>
    <td>请求的地址</td>
      <td>GET</td>
    <td>v2/activity</td>
  </tr>
</table>
<p class="subtitlestyle">（二）参数列表：</p>
<table width="90%" border="1" class="dbTable">

 <?php require_once ("../include/required_or_optional.php"); ?>
 <?php require_once ("../include/token.required.php"); ?>
    <tr>
        <td>uuid</td>
        <td >活动ID</td>
        <td >是</td>
        <td ></td>
    </tr>
</table>
    <?php require_once ("../include/json_info.php"); ?>

    <p><span class="subtitlestyle">（四）特别备注</span>（infor字段说明，仅列出部分关键字段）</p>
    <table width="90%" border="1" class="dbTable">
        <tr class="td_header">
            <td width="16%">参数名称</td>
            <td width="27%">参数说明</td>
            <td width="57%">备注</td>
        </tr>
        <tr>
            <td>uuid</td>
            <td>活动ID</td>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <td>name</td>
            <td>名称</td>
            <td></td>
        </tr>
        <tr>
            <td>cover_image</td>
            <td>封面</td>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <td>create_time</td>
            <td>创建时间</td>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <td>join_count</td>
            <td>报名人数</td>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <td class="inforstyle">join_user</td>
            <td></td>
            <td>&nbsp;若用户还未参加，则为<span class="titlestyle"><font color="red">null</font> </span></td>
        </tr>
        <tr>
            <td>finish</td>
            <td>用户是否完成该活动</td>
            <td>&nbsp;0 否  1 是</td>
        </tr>


    </table>
    <p>&nbsp;</p>
</div>


<?php require_once("../include/foot.inc.php");?>