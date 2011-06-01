<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
        <meta http-equiv="X-UA-Compatible" content="IE=8" />
        <title>{TR_RESELLER_IP_USAGE_TITLE}</title>
        <meta name="robots" content="nofollow, noindex" />
        <link href="{THEME_COLOR_PATH}/css/imscp.css" rel="stylesheet" type="text/css" />
        <!--[if IE 6]>
        <script type="text/javascript" src="{THEME_COLOR_PATH}/js/DD_belatedPNG_0.0.8a-min.js"></script>
        <script type="text/javascript">
            DD_belatedPNG.fix('*');
        </script>
        <![endif]-->
    </head>
    <body>
        <div class="header">
            {MAIN_MENU}
            <div class="logo">
                <img src="{THEME_COLOR_PATH}/images/imscp_logo.png" alt="i-MSCP logo" />
            </div>
        </div>
        <div class="location">
            <div class="location-area icons-left">
                <h1 class="statistics">{TR_MENU_IP_USAGE}</h1>
            </div>
            <ul class="location-menu">
                <!-- <li><a class="help" href="#">Help</a></li> -->
                <!-- BDP: logged_from -->
                <li>
                    <a class="backadmin" href="change_user_interface.php?action=go_back">{YOU_ARE_LOGGED_AS}</a>
                </li>
                <!-- EDP: logged_from -->
                <li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a>
                </li>
            </ul>
            <ul class="path">
                <li><a href="server_statistic.php">{TR_DOMAIN_STATISTICS}</a></li>
                <li><a href="ip_usage.php">{TR_IP_RESELLER_USAGE_STATISTICS}</a></li>

            </ul>
        </div>
        <div class="left_menu">
            {MENU}
        </div>
        <div class="body">
            <h2 class="general"><span>{TR_IP_RESELLER_USAGE_STATISTICS}</span></h2>
            <!-- BDP: page_message -->
            <div class="{MESSAGE_CLS}">{MESSAGE}</div>
            <!-- EDP: page_message -->
            <!-- BDP: ip_usage_statistics -->
            <table>
                <!-- BDP: ip_row -->
                <thead>
                    <tr>
                        <th colspan="5">{IP}</th>
                    </tr>
                </thead>
                <tr>
                    <td>&nbsp;</td>
                    <td><b>{TR_DOMAIN_NAME}</b></td>
                </tr>
                <!-- BDP: domain_row -->
                <tr>
                    <td width="25">&nbsp;</td>
                    <td>{DOMAIN_NAME}</td>
                </tr>
                <!-- EDP: domain_row -->
                <tr>
                    <td>&nbsp;</td>
                    <td colspan="5"><b>{RECORD_COUNT}</b></td>
                </tr>
                <!-- EDP: ip_row -->
            </table>
        </div>
        <!-- EDP: ip_usage_statistics -->
        <div class="footer">
            i-MSCP {VERSION}<br />build: {BUILDDATE}<br />Codename: {CODENAME}
        </div>
    </body>
</html>
