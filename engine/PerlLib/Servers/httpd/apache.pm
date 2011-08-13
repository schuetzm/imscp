#!/usr/bin/perl

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010 - 2011 by internet Multi Server Control Panel
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
#
# @category		i-MSCP
# @copyright	2010 - 2011 by i-MSCP | http://i-mscp.net
# @author		Daniel Andreca <sci2tech@gmail.com>
# @version		SVN: $Id$
# @link			http://i-mscp.net i-MSCP Home Site
# @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Servers::httpd::apache;

use strict;
use warnings;
use iMSCP::Debug;
use Data::Dumper;

use vars qw/@ISA/;

@ISA = ('Common::SingletonClass');
use Common::SingletonClass;

sub _init{
	debug('Starting...');

	my $self				= shift;

	$self->{masterConf}		= '00_master.conf';
	$self->{masterSSLConf}	= '00_master_ssl.conf';

	$self->{cfgDir}	= "$main::imscpConfig{'CONF_DIR'}/apache";
	$self->{bkpDir}	= "$self->{cfgDir}/backup";
	$self->{wrkDir}	= "$self->{cfgDir}/working";
	$self->{tplDir}	= "$self->{cfgDir}/parts";

	my $conf		= "$self->{cfgDir}/apache.data";
	tie %self::apacheConfig, 'iMSCP::Config','fileName' => $conf;

	$self->{tplValues}->{$_} = $self::apacheConfig{$_} foreach(keys %self::apacheConfig);

	debug('Ending...');
	0;
}

sub install{
	debug('Starting...');

	use Servers::httpd::apache::installer;

	my $self	= shift;
	my $rs		= Servers::httpd::apache::installer->new()->install();

	debug('Ending...');
	$rs;
}

sub postinstall{
	debug('Starting...');

	my $self	= shift;
	$self->{restart} = 'yes';

	debug('Ending...');
	0;
}

sub setGuiPermissions{
	debug('Starting...');

	use Servers::httpd::apache::installer;

	my $self	= shift;
	my $rs = Servers::httpd::apache::installer->new()->setGuiPermissions();

	debug('Ending...');
	$rs;
}

sub registerPreHook{
	debug('Starting...');

	my $self		= shift;
	my $fname		= shift;
	my $callback	= shift;

	my $installer	= Servers::httpd::apache::installer->new();

	push (@{$installer->{preCalls}->{$fname}}, $callback)
		if (ref $callback eq 'CODE' && $installer->can($fname));

	push (@{$self->{preCalls}->{$fname}}, $callback)
		if (ref $callback eq 'CODE' && $self->can($fname));

	debug('Ending...');
	0;
}

sub registerPostHook{
	debug('Starting...');

	my $self		= shift;
	my $fname		= shift;
	my $callback	= shift;

	debug("Attaching to $fname... $callback");

	my $installer	= Servers::httpd::apache::installer->new();

	push (@{$installer->{postCalls}->{$fname}}, $callback)
		if (ref $callback eq 'CODE' && $installer->can($fname));

	push (@{$self->{postCalls}->{$fname}}, $callback)
		if (ref $callback eq 'CODE' && $self->can($fname));

	debug('Ending...');
	0;
}

sub enableSite{
	debug('Starting...');

	use iMSCP::Execute;

	my $self	= shift;
	my $site	= shift;
	my ($rs, $stdout, $stderr);

	$rs = execute("a2ensite $site", \$stdout, \$stderr);
	debug("$stdout") if($stdout);
	error("$stderr") if($stderr);
	return $rs if $rs;

	debug('Ending...');
	0;
}

sub disableSite{
	debug('Starting...');

	use iMSCP::Execute;

	my $self	= shift;
	my $site	= shift;
	my ($rs, $stdout, $stderr);

	$rs = execute("a2dissite $site", \$stdout, \$stderr);
	debug("stdout $stdout") if($stdout);
	error("stderr $stderr") if($stderr);
	return $rs if $rs;

	debug('Ending...');
	0;
}

sub enableMod{
	debug('Starting...');

	use iMSCP::Execute;

	my $self	= shift;
	my $mod		= shift;
	my ($rs, $stdout, $stderr);

	$rs = execute("a2enmod $mod", \$stdout, \$stderr);
	debug("$stdout") if($stdout);
	error("$stderr") if($stderr);
	return $rs if $rs;

	debug('Ending...');
	0;
}

sub disableMod{
	debug('Starting...');

	use iMSCP::Execute;

	my $self	= shift;
	my $mod		= shift;
	my ($rs, $stdout, $stderr);

	$rs = execute("a2dismod $mod", \$stdout, \$stderr);
	debug("$stdout") if($stdout);
	error("$stderr") if($stderr);
	return $rs if $rs;

	debug('Ending...');
	0;
}
sub forceRestart{
	debug('Starting...');

	my $self			= shift;
	$self->{forceRestart} = 'yes';

	debug('Ending...');
	0;
}

sub restart{
	debug('Starting...');

	my $self			= shift;
	my ($rs, $stdout, $stderr);

	use iMSCP::Execute;

	# Reload apache config
	$rs = execute("$self->{tplValues}->{CMD_HTTPD} ".($self->{forceRestart} ? 'restart' : 'reload'), \$stdout, \$stderr);
	debug("$stdout") if $stdout;
	error("$stderr") if $stderr;
	error("Error while restating") if $rs && !$stderr;
	return $rs if $rs;

	debug('Ending...');
	0;
}

sub buildConf($ $ $){
	debug('Starting...');

	use iMSCP::Templator;

	my $self		= shift;
	my $cfgTpl		= shift;
	my $filename	= shift;

	error('Empty config template...') unless $cfgTpl;
	return undef  unless $cfgTpl;

	$self->{tplValues}->{$_} = $self->{data}->{$_} foreach(keys %{$self->{data}});
	warning('Nothing to do...') unless keys %{$self->{tplValues}} > 0;

	my @calls = exists $self->{preCalls}->{buildConf}
				?
				(@{$self->{preCalls}->{buildConf}})
				:
				()
	; # is a reason for this!!! Simplify code and you have infinite loop
	foreach(@calls){
		eval {$cfgTpl = &$_($cfgTpl, $filename);};
		error("$@") if ($@);
		return undef if $@;
	};
	#avoid running same hook again
	delete $self->{preCalls}->{buildConf};

	$cfgTpl = process($self->{tplValues}, $cfgTpl);
	return undef if (!$cfgTpl);

	#avoid running same hook again
	@calls = exists $self->{postCalls}->{buildConf}
				?
				(@{$self->{postCalls}->{buildConf}})
				:
				()
	; # is a reason for this!!! Simplify code and you have infinite loop
	foreach(@calls){
		eval {$cfgTpl = &$_($cfgTpl, $filename);};
		error("$@") if ($@);
		return undef if $@;
	};
	delete $self->{postCalls}->{buildConf};

	debug('Ending...');
	$cfgTpl;
}

sub buildConfFile{
	debug('Starting...');

	use File::Basename;
	use iMSCP::File;

	my $self	= shift;
	my $file	= shift;
	my $option	= shift;

	$option = {} if ref $option ne 'HASH';

	my ($filename, $directories, $suffix) = fileparse($file);

	$file = "$self->{cfgDir}/$file" unless -d $directories && $directories ne './';

	my $fileH = iMSCP::File->new(filename => $file);
	my $cfgTpl = $fileH->get();
	error("Empty config template $file...") unless $cfgTpl;
	return 1 unless $cfgTpl;

	my @calls = exists $self->{preCalls}->{buildConfFile}
				?
				(@{$self->{preCalls}->{buildConfFile}})
				:
				()
	; # is a reason for this!!! Simplify code and you have infinite loop
	foreach(@calls){
		eval {$cfgTpl = &$_($cfgTpl, "$filename$suffix");};
		error("$@") if ($@);
		return 1 if $@;
	}
	delete $self->{preCalls}->{buildConfFile};

	$cfgTpl = $self->buildConf($cfgTpl, "$filename$suffix");
	return 1 if (!$cfgTpl);

	@calls = exists $self->{postCalls}->{buildConfFile}
				?
				(@{$self->{postCalls}->{buildConfFile}})
				:
				()
	; # is a reason for this!!! Simplify code and you have infinite loop
	foreach(@calls){
		eval {$cfgTpl = &$_($cfgTpl, "$filename$suffix");};
		error("$@") if ($@);
		return 1 if $@;
	}
	delete $self->{postCalls}->{buildConfFile};

	$fileH = iMSCP::File->new(
				filename => ($option->{destination}
				?
				$option->{destination} :
				"$self->{wrkDir}/$filename$suffix")
	);
	$fileH->set($cfgTpl) and return 1;
	$fileH->save() and return 1;
	$fileH->mode($option->{mode} ? $option->{mode} : 0644) and return 1;
	$fileH->owner(
			$option->{user}		? $option->{user}	: $main::imscpConfig{'ROOT_USER'},
			$option->{group}	? $option->{group}	: $main::imscpConfig{'ROOT_GROUP'}
	) and return 1;

	debug('Ending...');
	0;
}

sub installConfFile{
	debug('Starting...');

	use File::Basename;
	use iMSCP::File;

	my $self	= shift;
	my $file	= shift;
	my $option	= shift;

	$option = {} if ref $option ne 'HASH';

	my ($filename, $directories, $suffix) = fileparse($file);

	$file = "$self->{wrkDir}/$file" unless -d $directories && $directories ne './';

	my $fileH = iMSCP::File->new(filename => $file);

	$fileH->mode($option->{mode} ? $option->{mode} : 0644) and return 1;
	$fileH->owner(
			$option->{user}		? $option->{user}	: $main::imscpConfig{'ROOT_USER'},
			$option->{group}	? $option->{group}	: $main::imscpConfig{'ROOT_GROUP'}
	) and return 1;

	$fileH->copyFile(
					$option->{destination}
					?
					$option->{destination} :
					"$self::apacheConfig{APACHE_SITES_DIR}/$filename$suffix"
	);

	debug('Ending...');
	0;
}

sub setData{
	debug('Starting...');

	my $self	= shift;
	my $data	= shift;

	$data = {} if ref $data ne 'HASH';
	$self->{data} = $data;

	debug('Ending...');
	0;
}

sub getRunningUser{
	debug('Starting...');
	debug('Ending...');
	return $self::apacheConfig{APACHE_USER};
}

sub getRunningGroup{
	debug('Starting...');
	debug('Ending...');
	return $self::apacheConfig{APACHE_GROUP};
}

sub addDomain{
	debug('Starting...');
	my $self = shift;
	my $data = shift;

	my $errmsg = {
		'DMN_NAME'	=> 'You must supply domain name!',
		'DMN_IP'	=> 'You must supply ip for domain!'
	};
	foreach(keys %{$errmsg}){
		error("$errmsg->{$_}") unless $data->{$_};
		return 1 unless $data->{$_};
	}

	my $rs = $self->addDomainCfg($data);
	return $rs if $rs;

	$rs = $self->addDomainFiles($data);
	return $rs if $rs;

	debug('Ending...');
	0;
}

sub addDomainFiles{
	debug('Starting...');

	use iMSCP::Dir;
	use iMSCP::Rights;

	my $self		= shift;
	my $data		= shift;
	my $uDir		= "$data->{WWW_DIR}";
	my $hDir		= "$data->{WWW_DIR}/$data->{MOUNT_POINT}/$data->{DMN_NAME}";
	$hDir			=~ s~/+~/~g;
	my $rootUser	= $main::imscpConfig{ROOT_USER};
	my $rootGroup	= $main::imscpConfig{ROOT_GROUP};
	my $apacheGroup	= $self::apacheConfig{APACHE_GROUP};
	my $newHtdocs	= -d "$hDir/htdocs";
	my $php5Dir	= "$self::apacheConfig{PHP_STARTER_DIR}/$data->{DMN_NAME}";

	for (
		["$hDir",			$data->{USER},	$apacheGroup,	0770],
		["$hDir/htdocs",	$data->{USER},	$data->{GROUP},	0755],
		["$hDir/cgi-bin",	$data->{USER},	$data->{GROUP},	0755],
		["$hDir/phptmp",	$data->{USER},	$apacheGroup,	0770],
		["$hDir/errors",	$data->{USER},	$data->{GROUP},	0775],
		["$php5Dir",		$data->{USER},	$data->{GROUP},	0555],
		["$php5Dir/php5",	$data->{USER},	$data->{GROUP},	0550]
	){
		iMSCP::Dir->new( dirname => $_->[0])->make({
			user	=> $_->[1],
			group	=> $_->[2],
			mode	=> $_->[3]
		});
	}

	my ($rs, $stdout, $stderr);

	unless ($newHtdocs){
		my $sourceDir	= "$main::imscpConfig{GUI_ROOT_DIR}/data/domain_default_page";
		my $dstDir		= "$hDir/htdocs/";
		my $fileSource =
		my $destFile	= "$hDir/htdocs/index.html";

		$rs = execute("cp -vnRT $sourceDir $dstDir", \$stdout, \$stderr);
		debug("$stdout") if $stdout;
		error("$stderr") if $stderr;

		$rs = $self->buildConfFile($fileSource, {destination => $destFile});

		$rs = setRights(
			$dstDir,
			{
				user		=> $data->{USER},
				group		=> $apacheGroup,
				filemode	=> '0640',
				dirmode		=> '0755',
				recursive	=> 'yes'
			}
		);
	}

	my $sourceDir	= "$main::imscpConfig{GUI_ROOT_DIR}/data/domain_disable_page";
	my $dstDir		= "$hDir/domain_disable_page";
	my $fileSource =
	my $destFile	= "$hDir/domain_disable_page/index.html";

	$rs = execute("cp -vnRT $sourceDir $dstDir", \$stdout, \$stderr);
	debug("$stdout") if $stdout;
	error("$stderr") if $stderr;

	$rs = $self->buildConfFile($fileSource, {destination => $destFile});

	$rs = setRights(
		"$hDir/domain_disable_page",
		{
			user		=> $rootUser,
			group		=> $apacheGroup,
			filemode	=> '0640',
			dirmode		=> '0750',
			recursive	=> 'yes'
		}
	);

	$rs = execute("cp -vnRT $main::imscpConfig{GUI_ROOT_DIR}/public/errordocs $hDir/errors", \$stdout, \$stderr);
	debug("$stdout") if $stdout;
	error("$stderr") if $stderr;

	$rs = setRights(
		"$hDir/errors",
		{
			user		=> $data->{USER},
			group		=> $apacheGroup,
			filemode	=> '0640',
			dirmode		=> '0755',
			recursive	=> 'yes'
		}
	);

	$fileSource	= "$main::imscpConfig{CONF_DIR}/fcgi/parts/php5-fcgi-starter.tpl";
	$destFile	= "$php5Dir/php5-fcgi-starter";
	$rs = $self->buildConfFile($fileSource, {destination => $destFile});
	$rs = setRights($destFile,
		{
			user	=> $data->{USER},
			group	=> $data->{GROUP},
			mode	=> '0550',
		}
	);

	$fileSource	= "$main::imscpConfig{CONF_DIR}/fcgi/parts/php5/php.ini";
	$destFile	= "$php5Dir/php5/php.ini";
	$rs = $self->buildConfFile($fileSource, {destination => $destFile});
	$rs = setRights($destFile,
		{
			user	=> $data->{USER},
			group	=> $data->{GROUP},
			mode	=> '0440',
		}
	);

	my $file;
	for(
		"$data->{WWW_DIR}/$self::apacheConfig{HTACCESS_USERS_FILE_NAME}",
		"$data->{WWW_DIR}/$self::apacheConfig{HTACCESS_GROUPS_FILE_NAME}",
		"$self::apacheConfig{SCOREBOARDS_DIR}/$data->{USER}"
	){
		$file = iMSCP::File->new(filename => $_)->save() unless( -f $_);
	}

	debug('Ending...');
	0;
}

sub addDomainCfg{
	debug('Starting...');

	use iMSCP::File;

	my $self = shift;
	my $data = shift;
	my $rs;

	$self->{data} = $data;

	$self->registerPostHook(
		'buildConf', sub { return $self->removeSection('cgi support', @_); }
	) unless ($data->{have_cgi} && $data->{have_cgi} eq 'yes');

	$self->registerPostHook(
		'buildConf', sub { return $self->removeSection('php enabled', @_); }
	) unless ($data->{have_php} && $data->{have_php} eq 'yes');

	$self->registerPostHook(
		'buildConf', sub { return $self->removeSection('php disabled', @_); }
	) if ($data->{have_php} && $data->{have_php} eq 'yes');

	for(/$data->{DMN_NAME}.conf 00_nameserver.conf 00_modcband.conf/){
		iMSCP::File->new(
			filename => "$self->{cfgDir}/$_"
		)->copyFile(
			"$self->{bkpDir}/$_". time
		) if (-f "$self->{cfgDir}/$_");
	}

	$rs = $self->buildConfFile(
		"$self->{tplDir}/domain.tpl",
		{destination => "$self->{wrkDir}/$data->{DMN_NAME}.conf"}
	);

	return $rs if $rs;

	$rs = $self->installConfFile("$data->{DMN_NAME}.conf");
	return $rs if $rs;

	my $filename = (
		-f "$self->{wrkDir}/00_nameserver.conf"
		?
		"$self->{wrkDir}/00_nameserver.conf"
		:
		"$self->{tplDir}/00_nameserver.tpl"
	);

	my $file = iMSCP::File->new(filename => $filename);
	my $content = $file->get();
	$content.= "NameVirtualHost $data->{DMN_IP}:80\n"
		if($content !~ /NameVirtualHost $data->{DMN_IP}:80/gi);

	$file = iMSCP::File->new(filename => "$self->{wrkDir}/00_nameserver.conf");
	$file->set($content);
	$file->save() and return 1;

	$rs = $self->installConfFile("00_nameserver.conf");
	return $rs if $rs;

	$filename = (
		-f "$self->{wrkDir}/00_modcband.conf"
		?
		"$self->{wrkDir}/00_modcband.conf"
		:
		"$self->{tplDir}/modcband.tpl"
	);

	$file	= iMSCP::File->new(filename => $filename);
	$content	= $file->get();
	my $bTag	= "## SECTION {USER} BEGIN.";
	my $eTag	= "## SECTION {USER} END.";
	my $bUTag	= "## SECTION $data->{USER} BEGIN.";
	my $eUTag	= "## SECTION $data->{USER} END.";

	my $entry	= getBloc($bTag, $eTag, $content);
	chomp($entry);
	$entry		=~ s/#//g;

	$content	= replaceBloc($bUTag, $eUTag, '', $content, undef);
	chomp($content);

	$self->{data}->{BWLIMIT_DISABLED} = ($data->{BWLIMIT} ? '' : '#');
	$entry	= $self->buildConf($bTag.$entry.$eTag);
	$content	= replaceBloc($bTag, $eTag, $entry, $content, 'yes');

	$file = iMSCP::File->new(filename => "$self->{wrkDir}/00_modcband.conf");
	$file->set($content);
	$file->save() and return 1;

	$rs = $self->installConfFile("00_modcband.conf");
	return $rs if $rs;

	$rs = $self->buildConfFile(
		"$self->{tplDir}/custom.conf.tpl",
		{destination => "$self::apacheConfig{APACHE_CUSTOM_SITES_CONFIG_DIR}/$data->{DMN_NAME}.conf"}
	) unless (-f "$self::apacheConfig{APACHE_CUSTOM_SITES_CONFIG_DIR}/$data->{DMN_NAME}.conf");
	return $rs if $rs;

	$rs = $self->enableSite("00_modcband.conf 00_nameserver.conf $data->{DMN_NAME}.conf");
	return $rs if $rs;

	debug('Ending...');
	0;
}

sub postAddDmn{
	debug('Starting...');

	my $self			= shift;
	$self->{restart}	= 'yes';
	delete $self->{data};


	debug('Ending...');
	0;
}

sub removeSection{
	debug('Starting...');

	use iMSCP::Templator;

	my $self	= shift;
	my $section	= shift;
	my $data	= shift;
	my $bTag = "# SECTION $section BEGIN.";
	my $eTag = "# SECTION $section END.";
	debug("$section...");

	$data = replaceBloc($bTag, $eTag, '', $data, undef);

	debug('Ending...');
	$data;
}

sub delDomain{
	debug('Starting...');

	my $self	= shift;
	my $data = shift;

	error('You must supply domain name!') unless $data->{DMN_NAME};
	return 1 unless $data->{DMN_NAME};

	my $rs = 0;

	$rs |= $self->disableSite("$data->{DMN_NAME}.conf");

	for(
		"$self::apacheConfig{APACHE_SITES_DIR}/$data->{DMN_NAME}.conf",
		"$self::apacheConfig{APACHE_CUSTOM_SITES_CONFIG_DIR}/$data->{DMN_NAME}.conf",
		"$self->{wrkDir}/$data->{DMN_NAME}.conf",
	){
		$rs |= iMSCP::File->new(filename => $_)->delFile() if -f $_;
	}

	my $hDir		= "$data->{WWW_DIR}/$data->{MOUNT_POINT}/$data->{DMN_NAME}";
	$hDir			=~ s~/+~/~g;

	for(
		"$self::apacheConfig{PHP_STARTER_DIR}/$data->{DMN_NAME}",
		"$hDir",
	){
		$rs |= iMSCP::Dir->new(dirname => $_)->remove() if -d $_;
	}

	debug('Ending...');
	$rs;
}

sub postDelDmn{
	debug('Starting...');

	my $self			= shift;
	$self->{restart}	= 'yes';
	delete $self->{data};

	debug('Ending...');
	0;
}

sub DESTROY{
	debug('Starting...');

	my $self	= shift;
	my $rs		= 0;
	$rs			= $self->restart()
		if $self->{restart} && $self->{restart} eq 'yes';

	debug('Ending...');
	$rs;
}

1;
