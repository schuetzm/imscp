<?php

/**
 * Generate the TLD values in /orderpanel/addon.php
 *
 * @author Peter Ziergoebel <info@fisa4.de>
 * @since 1.0.0 (i-MSCP)
 * @param int $hp_id, $user_id
 * @return NONE
 */
function gen_tld_list(&$tpl, &$sql, $hp_id, $user_id) {

    global $jump;

	$cfg = iMSCP_Registry::get('Config');

	$ok_status = $cfg->ITEM_OK_STATUS;

	$query = "
		SELECT
			`tld`
		FROM
			`hosting_plans`
		WHERE
			`id` = ?
		AND
			`reseller_id` = ?
	";

	$rs = exec_query($sql, $query, array($hp_id, $user_id));

	if (!$rs->recordCount()) {
		$tpl->assign(
			array(
				'OP_TLD' => 'no available TLD',
			)
		);
      
		$tpl->parse('OP_TLD_LIST', 'op_tld_list');
	} else {
		$first_passed = false;
        $tlds = str_getcsv($rs->fields['tld'], ";");
		$count = count($tlds);
        $pos = 0;
        while ($pos < $count) {
            $current_tld = $tlds[$pos];
            if($current_tld == ""){
                $jump = TRUE;
            } else {
                $jump = FALSE;
            }
            if(!$jump == TRUE) {
                $tpl->assign(
                    array(
                        'OP_TLD'		=> $current_tld,
                    )
                );
			$tpl->parse('OP_TLD_LIST', '.op_tld_list');
			}
            $pos ++;
            $rs->moveNext();

			if (!$first_passed)
				$first_passed = true;
		}
	}
}

/**
 * Missing function in PHP < 5.3.0
 * place a CSV string into an array.
 *
 * @author From a comment of php.net
 * @since 1.0.0 (i-MSCP)
 * @param string $input
 * @optional_param [, string $delimiter = ';' [, string $enclosure = '"' [, string $escape = '\\' ]]]
 * @return array $data
 */
if (!function_exists('str_getcsv')) {
    function str_getcsv($input, $delimiter = ";", $enclosure = '"', $escape = "\\") {
        $input = str_replace(",",";",$input);
        $fiveMBs = 5 * 1024 * 1024;
        $fp = fopen("php://temp/maxmemory:$fiveMBs", 'r+');
        fputs($fp, $input);
        rewind($fp);

        $data = fgetcsv($fp, 1000, $delimiter, $enclosure); //  $escape only got added in 5.3.0

        fclose($fp);
        return $data;
    }
} 
?>