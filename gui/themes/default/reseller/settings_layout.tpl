<!-- INCLUDE "../shared/layout/header.tpl" -->
		<div class="header">
			{MAIN_MENU}
			<div class="logo">
				<img src="{ISP_LOGO}" alt="i-MSCP logo" />
			</div>
		</div>
		<div class="location">
			<div class="location-area">
				<h1 class="general">{TR_GENERAL_INFO}</h1>
			</div>
			<ul class="location-menu">
				<!-- BDP: logged_from -->
				<li><a class="backadmin" href="change_user_interface.php?action=go_back">{YOU_ARE_LOGGED_AS}</a></li>
				<!-- EDP: logged_from -->
				<li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
			</ul>
			<ul class="path">
				<li><a href="index.php">{TR_GENERAL_INFO}</a></li>
				<li><a href="settings_layout.php">{TR_LAYOUT_SETTINGS}</a></li>
			</ul>
		</div>
		<div class="left_menu">
			{MENU}
		</div>
		<div class="body">
			<h2 class="layout"><span>{TR_LAYOUT_SETTINGS}</span></h2>

			<!-- BDP: page_message -->
			<div class="{MESSAGE_CLS}">{MESSAGE}</div>
			<!-- EDP: page_message -->

			<table>
				<tr>
					<th>{TR_LOGO_FILE}</th>
				</tr>
				<!-- BDP: logo_remove_button -->
				<tr>
					<td style="text-align:center;">
						<img src="{OWN_LOGO}" alt="reseller logo" />
						<form method="post" action="settings_layout.php">
							<div class="buttons">
								<input type="hidden" name="uaction" value="deleteIspLogo" />
								<input name="Submit" type="submit" class="button" value="{TR_REMOVE}" />
							</div>
						</form>
					</td>
				</tr>
				<!-- EDP: logo_remove_button -->
				<tr>
					<td>
						<form enctype="multipart/form-data" name="set_layout" method="post" action="settings_layout.php">
							<input type="file" name="logoFile" />
							<div class="buttons" style="display: inline;">
								<input type="hidden" name="uaction" value="updateIspLogo" />
								<input name="Submit" type="submit" class="button" value="{TR_UPLOAD}" />
							</div>
						</form>
					</td>
				</tr>
			</table>
			<!-- BDP: layout_colors_block -->
			<form class="layoutColor" method="post" action="settings_layout.php">
				<table>
					<tr>
						<th colspan="2">{TR_LAYOUT_COLOR}</th>
					</tr>
					<tr>
						<td style="width: 250px;"><label for="layoutColor">{TR_CHOOSE_LAYOUT_COLOR}</label></td>
						<td>
							<select name="layoutColor" id="layoutColor">
								<!-- BDP: layout_color_block -->
								<option value="{COLOR}" {SELECTED_COLOR}>{COLOR}</option>
								<!-- EDP: layout_color_block -->
							</select>
							<input name="Submit" type="submit" class="button" value="{TR_CHANGE}"/>
						</td>
					</tr>
				</table>
				<input type="hidden" name="uaction" value="changeLayoutColor"/>
			</form>
			<!-- EDP: layout_colors_block -->
		</div>
<!-- INCLUDE "../shared/layout/footer.tpl" -->
