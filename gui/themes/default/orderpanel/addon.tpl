<!-- BDP: purchase_header -->
<!-- EDP: purchase_header -->

<form name="addon" method="post" action="addon.php">
	<table width="400">
		<tr>
			<td colspan="2" class="content3"><strong>{DOMAIN_ADDON}</strong></td>
		</tr>

		<!-- BDP: page_message -->
		<tr>
			<td colspan="2" class="title" style="color:red;">{MESSAGE}</td>
		</tr>
		<!-- EDP: page_message -->

		<tr>
			<td class="content2">{TR_DOMAIN_NAME}</td>
			<td class="content">www.
				<input name="domainname" type="text" class="textinput" style="width:210px" />
				<select name="tld">
				    <option value="select">TLD</option>
				    <!-- BDP: op_tld_list -->
                    <option value="{OP_TLD}">{OP_TLD}</option>
                     <!-- EDP: op_tld_list -->
				</select>
				<br />
				<small>{TR_EXAMPLE}</small>
			</td>
		</tr>
		<tr>
			<td class="content">{TR_RADIO_NEW_KK}</td><td class="content2"><input type="radio" name="new_kk" value="new" checked> {VL_NEW} <input type="radio" name="new_kk" value="kk"> {VL_KK} <input type="radio" name="new_kk" value="hosting_only"> {ONLY_HOSTING}</td>
		</tr>
		<tr>
			<td colspan="2">&nbsp;</td>
		</tr>
		<tr align="right">
			<td colspan="2"><input name="Submit" type="submit" class="button" value="  {TR_CONTINUE}  " /></td>
		</tr>
	</table>
</form>
<br />

<!-- BDP: purchase_footer -->
<!-- EDP: purchase_footer -->
