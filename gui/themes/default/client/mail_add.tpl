<!-- INCLUDE "../shared/layout/header.tpl" -->
<body onload="begin_js();">
	<script type="text/javascript">
	/* <![CDATA[ */
		$(document).ready(function(){
			$('#fwd_help').iMSCPtooltips({msg:"{TR_FWD_HELP}"});
		});

		function changeType() {
			if (document.forms[0].elements['mail_type_normal'].checked == true) {
				document.forms[0].pass.disabled = false;
				document.forms[0].pass_rep.disabled = false;
			} else {
				document.forms[0].pass.disabled = true;
				document.forms[0].pass_rep.disabled = true;
			}
			if (document.forms[0].elements['mail_type_forward'].checked == true) {
				document.forms[0].forward_list.disabled = false;
			} else {
				document.forms[0].forward_list.disabled = true;
			}
		}

	function begin_js() {
		if (document.getElementsByName('als_id').length !== 0) {
			if (document.getElementById('dmn_type2').checked) {
				document.forms[0].als_id.disabled = false;
			} else {
				document.forms[0].als_id.disabled = true;
			}
		}
		if (document.getElementsByName('sub_id').length !== 0) {
			if (document.getElementById('dmn_type3').checked) {
				document.forms[0].sub_id.disabled = false;
			} else {
				document.forms[0].sub_id.disabled = true;
			}
		}
		if (document.getElementsByName('als_sub_id').length !== 0) {
			if (document.getElementById('dmn_type4').checked) {
				document.forms[0].als_sub_id.disabled = false;
			} else {
				document.forms[0].als_sub_id.disabled = true;
			}
		}
//		document.forms[0].pass.disabled = false;
//		document.forms[0].pass_rep.disabled = false;
//		document.forms[0].forward_list.disabled = true;
		changeType();
		document.forms[0].username.focus();
	}

	function changeDom(what) {
		if (document.getElementsByName('als_id').length !== 0) {
			if (what == "alias") {
				document.forms[0].als_id.disabled = false;
			} else {
				document.forms[0].als_id.disabled = true;
			}
		}
		if (document.getElementsByName('sub_id').length !== 0) {
			if (what == "subdom") {
				document.forms[0].sub_id.disabled = false;
			} else  {
				document.forms[0].sub_id.disabled = true;
			}
		}
		if (document.getElementsByName('als_sub_id').length !== 0) {
			if (what == "als_subdom") {
				document.forms[0].als_sub_id.disabled = false;
			} else {
				document.forms[0].als_sub_id.disabled = true;
			}
		}
	}
	/* ]]> */
	</script>
	<div class="header">
		{MAIN_MENU}
		<div class="logo">
			<img src="{ISP_LOGO}" alt="i-MSCP logo" />
		</div>
	</div>
	<div class="location">
		<div class="location-area">
			<h1 class="email">{TR_MENU_MAIL_ACCOUNTS}</h1>
		</div>
		<ul class="location-menu">
			<!-- BDP: logged_from -->
			<li><a class="backadmin" href="change_user_interface.php?action=go_back">{YOU_ARE_LOGGED_AS}</a></li>
			<!-- EDP: logged_from -->
			<li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
		</ul>
		<ul class="path">
			<li><a href="mail_accounts.php">{TR_MENU_MAIL_ACCOUNTS}</a></li>
			<li><a href="#" onclick="return false;">{TR_LMENU_ADD_MAIL_USER}</a></li>
		</ul>
	</div>

	<div class="left_menu">
		{MENU}
	</div>

	<div class="body">
		<h2 class="email"><span>{TR_ADD_MAIL_USER}</span></h2>

		<!-- BDP: page_message -->
		<div class="{MESSAGE_CLS}">{MESSAGE}</div>
		<!-- EDP: page_message -->

		<form action="mail_add.php" method="post" id="client_mail_add">
			<table>
				<tr>
					<th colspan="2">{TR_MAIl_ACCOUNT_DATA}</th>
				</tr>
				<tr>
					<td style="width: 300px;"><label for="username">{TR_USERNAME}</label></td>
					<td><input type="text" name="username" id="username" value="{USERNAME}" /></td>
				</tr>
				<tr>
					<td>
						<input type="radio" name="dmn_type" id="dmn_type1" value="dmn" {MAIL_DMN_CHECKED} onclick="changeDom('real');" />
						<label for="dmn_type1">{TR_TO_MAIN_DOMAIN}</label>
			  		</td>
			  		<td>{DOMAIN_NAME}</td>
				</tr>
				<!-- BDP: to_alias_domain -->
				<tr>
					<td>
						<input type="radio" name="dmn_type" id="dmn_type2" value="als" {MAIL_ALS_CHECKED} onclick="changeDom('alias');" />
						<label for="dmn_type2">{TR_TO_DMN_ALIAS}</label>
					</td>
					<td>
						<select name="als_id">
							<!-- BDP: als_list -->
							<option value="{ALS_ID}" {ALS_SELECTED}>{ALS_NAME}</option>
							<!-- EDP: als_list -->
						</select>
					</td>
				</tr>
				<!-- EDP: to_alias_domain -->
				<!-- BDP: to_subdomain -->
				<tr>
					<td>
						<input type="radio" name="dmn_type" id="dmn_type3" value="sub" {MAIL_SUB_CHECKED} onclick="changeDom('subdom');" />
						<label for="dmn_type3">{TR_TO_SUBDOMAIN}</label>
					</td>
					<td>
						<select name="sub_id">
							<!-- BDP: sub_list -->
							<option value="{SUB_ID}" {SUB_SELECTED}>{SUB_NAME}</option>
							<!-- EDP: sub_list -->
						</select>
					</td>
				</tr>
				<!-- EDP: to_subdomain -->
				<!-- BDP: to_alias_subdomain -->
				<tr>
					<td>
						<input type="radio" name="dmn_type" id="dmn_type4" value="als_sub" {MAIL_ALS_SUB_CHECKED} onclick="changeDom('als_subdom');" />
						<label for="dmn_type4">{TR_TO_ALS_SUBDOMAIN}</label>
					</td>
					<td>
						<select name="als_sub_id">
							<!-- BDP: als_sub_list -->
							<option value="{ALS_SUB_ID}" {ALS_SUB_SELECTED}>{ALS_SUB_NAME}</option>
							<!-- EDP: als_sub_list -->
						</select>
					</td>
				</tr>
				<!-- EDP: to_alias_subdomain -->
				<tr>
					<td colspan="2"><input type="checkbox" name="mail_type_normal" value="1" onclick="changeType();" {NORMAL_MAIL_CHECKED} />{TR_NORMAL_MAIL}</td>
				</tr>
				<tr>
					<td><label for="pass">{TR_PASSWORD}</label></td>
					<td><input id="pass" type="password" name="pass" value="" /></td>
				</tr>
				<tr>
					<td><label for="pass_rep">{TR_PASSWORD_REPEAT}</label></td>
					<td><input id="pass_rep" type="password" name="pass_rep" value="" /></td>
				</tr>
				<tr>
					<td colspan="2"><input type="checkbox" name="mail_type_forward" value="1" {FORWARD_MAIL_CHECKED} onclick="changeType();" />{TR_FORWARD_MAIL}</td>
				</tr>
				<tr>
					<td>
						<label for="forward_list">{TR_FORWARD_TO}</label><span class="icon i_help" id="fwd_help">Help</span>
					</td>
					<td><textarea name="forward_list" id="forward_list" cols="35" rows="5">{FORWARD_LIST}</textarea></td>
				</tr>
			</table>
			<div class="buttons">
				<input type="hidden" name="uaction" value="add_user" />
				<input type="submit" name="submit" value="{TR_ADD}" />
			</div>
		</form>
	</div>
<!-- INCLUDE "../shared/layout/footer.tpl" -->
