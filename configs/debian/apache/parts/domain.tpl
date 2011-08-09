<VirtualHost {DMN_IP}:80>

	<IfModule suexec_module>
		   SuexecUserGroup {USER} {GROUP}
	</IfModule>

	<IfModule mpm_itk_module>
		   AssignUserID {USER} {GROUP}
	</IfModule>

	ServerAdmin	 webmaster@{DMN_NAME}
	DocumentRoot	{WWW_DIR}/{DMN_NAME}/htdocs

	ServerName	  {DMN_NAME}
	ServerAlias	 www.{DMN_NAME} {DMN_NAME} {DMN_NAME}.{BASE_SERVER_VHOST}

	Alias /errors   {WWW_DIR}/{DMN_NAME}/errors/

	RedirectMatch permanent ^/ftp[\/]?$		{BASE_SERVER_VHOST_PREFIX}{BASE_SERVER_VHOST}/ftp/
	RedirectMatch permanent ^/pma[\/]?$		{BASE_SERVER_VHOST_PREFIX}{BASE_SERVER_VHOST}/pma/
	RedirectMatch permanent ^/webmail[\/]?$	{BASE_SERVER_VHOST_PREFIX}{BASE_SERVER_VHOST}/webmail/
	RedirectMatch permanent ^/imscp[\/]?$	{BASE_SERVER_VHOST_PREFIX}{BASE_SERVER_VHOST}/

	ErrorDocument 401 /errors/401.html
	ErrorDocument 403 /errors/403.html
	ErrorDocument 404 /errors/404.html
	ErrorDocument 500 /errors/500.html
	ErrorDocument 503 /errors/503.html

	<IfModule mod_cband.c>
		CBandUser {USER}
	</IfModule>

	# SECTION awstats support BEGIN.

	# SECTION awstats dinamic BEGIN.
		ProxyRequests Off
		<Proxy *>
			Order deny,allow
			Allow from all
		</Proxy>
		ProxyPass			/stats	http://localhost/stats/{DMN_NAME}
		ProxyPassReverse	/stats	http://localhost/stats/{DMN_NAME}
		<Location /stats>
			<IfModule mod_rewrite.c>
				RewriteEngine on
				RewriteRule ^(.+)\?config=([^\?\&]+)(.*) $1\?config={DMN_NAME}&$3 [NC,L]
			</IfModule>
			AuthType Basic
			AuthName "Statistics for domain {DMN_NAME}"
			AuthUserFile {WWW_DIR}/{DMN_NAME}/{HTACCESS_USERS_FILE_NAME}
			AuthGroupFile {WWW_DIR}/{DMN_NAME}/{HTACCESS_GROUPS_FILE_NAME}
			Require group {AWSTATS_GROUP_AUTH}
		</Location>
	# SECTION awstats dinamic END.

	# SECTION awstats static BEGIN.
		Alias /awstatsicons 	"{AWSTATS_WEB_DIR}/icon/"
		Alias /stats			"{WWW_DIR}/{DMN_NAME}/statistics/"
		<Directory "{WWW_DIR}/{DMN_NAME}/statistics">
			AllowOverride AuthConfig
			DirectoryIndex awstats.{DMN_NAME}.html
			Order allow,deny
			Allow from all
		</Directory>
		<Location /stats>
			AuthType Basic
			AuthName "Statistics for domain {DMN_NAME}"
			AuthUserFile {WWW_DIR}/{DMN_NAME}/{HTACCESS_USERS_FILE_NAME}
			AuthGroupFile {WWW_DIR}/{DMN_NAME}/{HTACCESS_GROUPS_FILE_NAME}
			Require group {AWSTATS_GROUP_AUTH}
		</Location>
	# SECTION awstats static END.

	# SECTION awstats support END.

	# SECTION cgi support BEGIN.
		ScriptAlias /cgi-bin/ {WWW_DIR}/{DMN_NAME}/cgi-bin/
		<Directory {WWW_DIR}/{DMN_NAME}/cgi-bin>
			AllowOverride AuthConfig
			#Options ExecCGI
			Order allow,deny
			Allow from all
		</Directory>
	# SECTION cgi support END.

	<Directory {WWW_DIR}/{DMN_NAME}/htdocs>
		# httpd dmn entry PHP support BEGIN.
		# httpd dmn entry PHP support END.
		Options -Indexes Includes FollowSymLinks MultiViews
		AllowOverride All
		Order allow,deny
		Allow from all
	</Directory>

	# SECTION php enabled BEGIN.
		<IfModule mod_php5.c>
			php_admin_value open_basedir "{WWW_DIR}/{DMN_NAME}/:{WWW_DIR}/{DMN_NAME}/phptmp/:{PEAR_DIR}/"
			php_admin_value upload_tmp_dir "{WWW_DIR}/{DMN_NAME}/phptmp/"
			php_admin_value session.save_path "{WWW_DIR}/{DMN_NAME}/phptmp/"
			php_admin_value sendmail_path '/usr/sbin/sendmail -f {USER} -t -i'
		</IfModule>
		<IfModule mod_fastcgi.c>
			ScriptAlias /php5/ {PHP_STARTER_DIR}/{DMN_NAME}/
			<Directory "{PHP_STARTER_DIR}/{DMN_NAME}">
				AllowOverride None
				Options +ExecCGI -MultiViews -Indexes
				Order allow,deny
				Allow from all
			</Directory>
		</IfModule>
		<IfModule mod_fcgid.c>
			<Directory {WWW_DIR}/{DMN_NAME}/htdocs>
				FCGIWrapper {PHP_STARTER_DIR}/{DMN_NAME}/php{PHP_VERSION}-fcgi-starter .php
				Options +ExecCGI
			</Directory>
			<Directory "{PHP_STARTER_DIR}/{DMN_NAME}">
				AllowOverride None
				Options +ExecCGI MultiViews -Indexes
				Order allow,deny
				Allow from all
			</Directory>
		</IfModule>
	# SECTION php enabled END.

	# SECTION php disabled BEGIN.
		<IfModule mod_php5.c>
			php_admin_flag engine off
		</IfModule>
		<IfModule mod_fastcgi.c>
			RemoveHandler .php
			RemoveType .php
		</IfModule>
		<IfModule mod_fcgid.c>
			RemoveHandler .php
			RemoveType .php
		</IfModule>
	# SECTION php disabled END.

	Include {APACHE_CUSTOM_SITES_CONFIG_DIR}/{DMN_NAME}.conf

</VirtualHost>
