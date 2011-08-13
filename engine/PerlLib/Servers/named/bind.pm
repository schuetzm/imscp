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

package Servers::named::bind;

use strict;
use warnings;
use iMSCP::Debug;

use vars qw/@ISA/;

@ISA = ('Common::SingletonClass');
use Common::SingletonClass;

sub _init{
	my $self	= shift;

	debug('Starting...');

	$self->{cfgDir} = "$main::imscpConfig{'CONF_DIR'}/bind";
	$self->{bkpDir} = "$self->{cfgDir}/backup";
	$self->{wrkDir} = "$self->{cfgDir}/working";
	$self->{tplDir}	= "$self->{cfgDir}/parts";

	$self->{commentChar} = '#';

	#$self->{$_} = $main::imscpConfig{$_} foreach(keys %main::imscpConfig);

	tie %self::bindConfig, 'iMSCP::Config','fileName' => "$self->{cfgDir}/bind.data", noerrors => 1;
	$self->{$_} = $self::bindConfig{$_} foreach(keys %self::bindConfig);
}

sub preinstall{
	debug('Starting...');

	use Servers::named::bind::installer;

	my $self	= shift;
	my $rs		= 0;

	debug('Ending...');
	$rs;
}

sub install{
	debug('Starting...');

	use Servers::named::bind::installer;

	my $self	= shift;
	my $rs		= Servers::named::bind::installer->new()->install();

	debug('Ending...');
	$rs;
}

sub postinst{
	debug('Starting...');

	my $self	= shift;
	my $rs		= $self->restart();

	debug('Ending...');
	$rs;
}

sub registerPreHook{
	debug('Starting...');

	my $self		= shift;
	my $fname		= shift;
	my $callback	= shift;

	my $installer	= Servers::named::bind::installer->new();

	push (@{$installer->{preCalls}->{fname}}, $callback)
		if (ref $callback eq 'CODE' && $installer->can($fname));

	push (@{$self->{preCalls}->{fname}}, $callback)
		if (ref $callback eq 'CODE' && $self->can($fname));

	debug('Ending...');
	0;
}

sub registerPostHook{
	debug('Starting...');

	my $self		= shift;
	my $fname		= shift;
	my $callback	= shift;

	debug("Attaching to $fname...");

	my $installer	= Servers::named::bind::installer->new();

	push (@{$installer->{postCalls}->{$fname}}, $callback)
		if (ref $callback eq 'CODE' && $installer->can($fname));

	push (@{$self->{postCalls}->{$fname}}, $callback)
		if (ref $callback eq 'CODE' && $self->can($fname));

	debug('Ending...');
	0;
}

sub restart{
	debug('Starting...');

	my $self			= shift;
	my ($rs, $stdout, $stderr);

	use iMSCP::Execute;

	# Reload config
	$rs = execute("$self->{CMD_NAMED} restart", \$stdout, \$stderr);
	debug("$stdout") if $stdout;
	error("$stderr") if $stderr;
	return $rs if $rs;

	debug('Ending...');
	0;
}

sub postAddDmn{
	debug('Starting...');

	my $self	= shift;
	my $option	= shift;

	$option = {} if ref $option ne 'HASH';

	my $errmsg = {
		'DMN_NAME'	=> 'You must supply domain name!',
		'DMN_IP'	=> 'You must supply ip for domain!'
	};
	foreach(keys %{$errmsg}){
		error("$errmsg->{$_}") unless $option->{$_};
		return 1 unless $option->{$_};
	}

	$self->addDomain({
			DMN_NAME	=> $main::imscpConfig{BASE_SERVER_VHOST},
			DMN_IP		=> $main::imscpConfig{BASE_SERVER_IP},
			DMN_ADD		=> {
				MANUAL_DNS_NAME		=> $option->{DMN_NAME},
				MANUAL_DNS_CLASS	=> 'IN',
				MANUAL_DNS_TYPE		=> 'A',
				MANUAL_DNS_DATA		=> $option->{DMN_IP}
			}
	}) and return 1;

	$self->{restart}	= 'yes';

	debug('Ending...');
	0;
}

sub addDomain{
	debug('Starting...');

	my $self	= shift;
	my $option	= shift;

	$option = {} if ref $option ne 'HASH';

	my $errmsg = {
		'DMN_NAME'	=> 'You must supply domain name!',
		'DMN_IP'	=> 'You must supply ip for domain!'
	};
	foreach(keys %{$errmsg}){
		error("$errmsg->{$_}") unless $option->{$_};
		return 1 unless $option->{$_};
	}

	$self->addDmnConfig($option) and return 1;
	$self->addDmnDb($option) and return 1
			if $self::bindConfig{BIND_MODE} =~ /^master$/i ;

	debug('Ending...');
	0;
}

sub addDmnDb {

	debug('Starting...');

	use iMSCP::Dialog;
	use iMSCP::File;
	use iMSCP::Templator;

	my $self		= shift;
	my $option		= shift;
	my $zoneFile	= "$self::bindConfig{BIND_DB_DIR}/$option->{DMN_NAME}.db";

	#Saving the current production file if it exists
	if(-f $zoneFile) {
		iMSCP::File->new(
			filename => $zoneFile
		)->copyFile(
			"$self->{bkpDir}/$option->{DMN_NAME}.db." . time
		) and return 1;
	}

	# Load the current working db file
	my $wrkCfg = "$self->{wrkDir}/$option->{DMN_NAME}.db";
	my $wrkFileContent = iMSCP::File->new(filename => $wrkCfg)->get() if(-f $wrkCfg);

	## Building new configuration file

	# Loading the template from /etc/imscp/bind/parts
	my $entries = iMSCP::File->new(filename => "$self->{tplDir}/db_e.tpl")->get();
	return 1 if (!$entries);

	##########################   NS SECTION START   #################################
	my $A_Sec_b		= '; ns A SECTION BEGIN';
	my $A_Sec_e		= '; ns A SECTION END';
	my $nsATpl		= getBloc($A_Sec_b, $A_Sec_e, $entries);
	chomp $nsATpl;

	my $Decl_b		= '; ns DECLARATION SECTION BEGIN';
	my $Decl_e		= '; ns DECLARATION SECTION END';
	my $nsDeclTpl	= getBloc($Decl_b, $Decl_e, $entries);
	chomp $nsDeclTpl;

	my $ns = 1;
	my ($nsASection, $nsDeclSection) = ('', '');
	my @ips = $self::bindConfig{SECONDARY_DNS} eq 'no' ? () : split(';', $self::bindConfig{SECONDARY_DNS});

	for($option->{DMN_IP}, @ips){
		$nsASection .= process({
				NS_NUMBER	=> $ns,
				NS_IP		=> $_
		}, $nsATpl);
		$nsDeclSection .= process({
				NS_NUMBER	=> $ns,
				NS_IP		=> $_
		}, $nsDeclTpl);
		$ns++;
	}
	$entries = replaceBloc($A_Sec_b, $A_Sec_e, $nsASection, $entries, undef);
	$entries = replaceBloc($Decl_b, $Decl_e, $nsDeclSection, $entries, undef);

	###########################   NS SECTION END   ##################################

	my $tags = {
		DMN_NAME			=> $option->{DMN_NAME},
		DMN_IP				=> $option->{DMN_IP},
		BASE_SERVER_IP		=> $main::imscpConfig{BASE_SERVER_IP}
	};

	# Replacement tags
	$entries = process($tags, $entries);
	return 1 if (!$entries);

	#######################   TIMESTAMP SECTION START   #############################
	# Create or Update serial number according RFC 1912
	my $bTag = process($tags, iMSCP::File->new(filename => "$self->{tplDir}/db_time_b.tpl")->get());
	my $eTag = process($tags, iMSCP::File->new(filename =>"$self->{tplDir}/db_time_e.tpl")->get());
	return 1 if(!$bTag || !$eTag);
	my $timestamp = getBloc($bTag, $eTag, ($wrkFileContent ? $wrkFileContent : $entries));
	my $regExp = '[\s](?:(\d{4})(\d{2})(\d{2})(\d{2})|(\{TIMESTAMP\}))';
	my (undef, undef, undef, $day, $mon, $year) = localtime;
	if((my $tyear, my $tmon, my $tday, my $nn, my $setup) = ($timestamp =~ /$regExp/)) {
		if($setup){
			$timestamp = sprintf '%04d%02d%02d00', $year+1900, $mon+1, $day;
		} else {
			$nn++;
			if($nn >= 99){
				$nn = 0;
				$tday++;
			}
			$timestamp = ((($year+1900)*10000+($mon+1)*100+$day) > ($tyear*10000 +  $tmon*100 + $tday)) ? (sprintf '%04d%02d%02d00', $year+1900, $mon+1, $day) : (sprintf '%04d%02d%02d%02d', $tyear, $tmon, $tday, $nn);
		}
		$entries = process({ TIMESTAMP => $timestamp}, $entries);
	} else {
		error("Can not find timestamp for $option->{DMN_NAME}");
		return 1;
	}
	########################   TIMESTAMP SECTION END   ##############################
	######################   COSTOM DATA SECTION START   ############################
	if( $option->{DMN_ADD} ){

		$bTag = '; ctm domain als entries BEGIN.';
		$eTag = '; ctm domain als entries END.';
		my $fTag = iMSCP::File->new(filename => "$self->{tplDir}/db_dns_entry.tpl")->get();
		my $old = iMSCP::File->new(filename => "$self->{wrkDir}/$option->{DMN_NAME}.db")->get() || '';

		$tags = {
			MANUAL_DNS_NAME		=> $option->{DMN_ADD}->{MANUAL_DNS_NAME},
			MANUAL_DNS_CLASS	=> $option->{DMN_ADD}->{MANUAL_DNS_CLASS},
			MANUAL_DNS_TYPE		=> $option->{DMN_ADD}->{MANUAL_DNS_TYPE},
			MANUAL_DNS_DATA		=> $option->{DMN_ADD}->{MANUAL_DNS_DATA}
		};

		my $toadd	= process($tags, $fTag);
		my $custom	= getBloc($bTag, $eTag, $old);
		$custom =~ s/$option->{DMN_ADD}->{MANUAL_DNS_NAME}\s[^\n]*\n//img;
		$custom = "\n" unless $custom;
		$custom = "$bTag$custom$toadd$eTag";

		$entries = replaceBloc($bTag, $eTag, $custom, $entries, undef);
	}
	#######################   COSTOM DATA SECTION END   #############################
	#####################   COSTUMERS DATA SECTION START   ##########################
	if( keys(%{$option->{DMN_CUSTOM}}) > 0 ){


		$bTag = iMSCP::File->new(filename => "$self->{tplDir}/db_dns_entry_b.tpl")->get();
		$eTag = iMSCP::File->new(filename =>"$self->{tplDir}/db_dns_entry_e.tpl")->get();
		my $FormatTag = iMSCP::File->new(filename => "$self->{tplDir}/db_dns_entry.tpl")->get();
		my $custom = '';

		for(keys %{$option->{DMN_CUSTOM}}){
			next unless
				$option->{DMN_CUSTOM}->{$_}->{domain_dns} &&
				$option->{DMN_CUSTOM}->{$_}->{domain_text} &&
				$option->{DMN_CUSTOM}->{$_}->{domain_class} &&
				$option->{DMN_CUSTOM}->{$_}->{domain_type};

			$tags = {
				MANUAL_DNS_NAME		=> $option->{DMN_CUSTOM}->{$_}->{domain_dns},
				MANUAL_DNS_CLASS	=> $option->{DMN_CUSTOM}->{$_}->{domain_class},
				MANUAL_DNS_TYPE		=> $option->{DMN_CUSTOM}->{$_}->{domain_type},
				MANUAL_DNS_DATA		=> $option->{DMN_CUSTOM}->{$_}->{domain_text}
			};

			$custom .= process($tags, $FormatTag);
		}

		$entries = replaceBloc($bTag, $eTag, $custom, $entries, undef);

	}
	#######################   COSTUMERS DATA SECTION END   ##########################

	## Store and install
	# Store the file in the working directory
	my $file = iMSCP::File->new(filename => $wrkCfg);
	$file->set($entries) and return 1;
	$file->save() and return 1;
	$file->mode(0644) and return 1;
	$file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'}) and return 1;

	# Install the file in the production directory
	$file->copyFile($self::bindConfig{BIND_DB_DIR}) and return 1;

	debug('Ending...');

	0;
}

sub addDmnConfig{
	debug('Starting...');

	use iMSCP::File;
	use iMSCP::Templator;
	use File::Basename;

	my $self	= shift;
	my $option	= shift;
	my ($rs, $rdata, $cfg, $file);

	##backup config file
	my $timestamp = time();
	if(-f "$self->{wrkDir}/named.conf"){
		my $file	= iMSCP::File->new( filename => "$self->{wrkDir}/named.conf" );
		my ($filename, $directories, $suffix) = fileparse("$self->{wrkDir}/named.conf");
		$file->copyFile("$self->{bkpDir}/$filename$suffix.$timestamp") and return 1;
	} else {
		error("$self->{wrkDir}/named.conf not found. Run setup again to fix this");
		return 1;
	}

	## Building of new configuration file

	# Loading all needed templates from /etc/imscp/bind/parts
	my ($entry_b, $entry_e, $entry) = ('', '', '');
	$entry_b	= iMSCP::File->new(filename => "$self->{tplDir}/cfg_entry_b.tpl")->get();
	$entry_e	= iMSCP::File->new(filename => "$self->{tplDir}/cfg_entry_e.tpl")->get();
	$entry		= iMSCP::File->new(filename => "$self->{tplDir}/cfg_entry_$self::bindConfig{BIND_MODE}.tpl")->get();
	return 1 if(!defined $entry_b ||!defined $entry_e ||!defined $entry);

	# Preparation tags
	my $tags_hash	= {
			DB_DIR			=> $self::bindConfig{BIND_DB_DIR},
			PRIMARY_DNS		=> join( '; ', split(';', $self::bindConfig{PRIMARY_DNS})).';',
	};
	for(qw/DMN_NAME/){
		$tags_hash->{$_} = $option->{$_}
	}
	my $entry_b_val	= process($tags_hash, $entry_b);
	my $entry_e_val	= process($tags_hash, $entry_e);
	my $entry_val	= process($tags_hash, $entry);


	# Loading working file from /etc/imscp/bind/working/named.conf
	$file		= iMSCP::File->new(filename => "$self->{wrkDir}/named.conf");
	$cfg		= $file->get();
	return 1 if (!$cfg);

	# Building the new configuration file
	my $entry_repl = "$entry_b_val$entry_val$entry_e_val$entry_b$entry_e";

	#delete old if exist
	$cfg = replaceBloc($entry_b_val, $entry_e_val, '', $cfg, undef);
	#add new
	$cfg = replaceBloc($entry_b, $entry_e, $entry_repl, $cfg, undef);

	## Storage and installation of new file - Begin

	# Store the new builded file in the working directory
	$file = iMSCP::File->new(filename => "$self->{wrkDir}/named.conf");
	$file->set($cfg) and return 1;
	$file->save() and return 1;
	$file->mode(0644) and return 1;
	$file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'}) and return 1;

	# Install the new file in the production directory
	$file->copyFile($self::bindConfig{BIND_CONF_FILE}) and return 1;

	debug('Ending...');
	0;
}

sub delDomain{
	debug('Starting...');

	my $self	= shift;
	my $option	= shift;
	my $rs;

	$option = {} if ref $option ne 'HASH';

	error('You must supply domain name!') unless $option->{DMN_NAME};
	return 1 unless $option->{DMN_NAME};

	$rs |= $self->delDmnConfig($option);

	my $zoneFile = "$self::bindConfig{BIND_DB_DIR}/$option->{DMN_NAME}.db";

	$rs |= iMSCP::File->new(filename => $zoneFile)->delFile() if -f $zoneFile;

	$rs |= iMSCP::File->new(
		filename => "$self->{wrkDir}/$option->{DMN_NAME}.db"
	)->delFile() if -f "$self->{wrkDir}/$option->{DMN_NAME}.db";

	$zoneFile = "$self->{wrkDir}/$main::imscpConfig{BASE_SERVER_VHOST}.db";
	$zoneFile = "$self::bindConfig{BIND_DB_DIR}/$main::imscpConfig{BASE_SERVER_VHOST}.db" unless -f $zoneFile;

	unless(-f $zoneFile) {
		error("$main::imscpConfig{BASE_SERVER_VHOST}.db do not exists");
		return 1;
	}

	my $zContent = iMSCP::File->new( filename => $zoneFile )->get();
	unless($zContent) {
		error("$main::imscpConfig{BASE_SERVER_VHOST}.db is empty");
		return 1;
	}

	$zContent =~ s/$option->{DMN_NAME}\s[^\n]*\n//gmi;

	# Store the new builded file in the working directory
	my $file = iMSCP::File->new(filename => "$self->{wrkDir}/$main::imscpConfig{BASE_SERVER_VHOST}.db");
	$file->set($zContent) and return 1;
	$file->save() and return 1;
	$file->mode(0644) and return 1;
	$file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'}) and return 1;

	# Install the new file in the production directory
	$file->copyFile($self::bindConfig{BIND_DB_DIR}) and return 1;

	debug('Ending...');
	$rs;
}

sub delDmnConfig{
	debug('Starting...');

	use iMSCP::File;
	use iMSCP::Templator;
	use File::Basename;

	my $self	= shift;
	my $option	= shift;
	my ($rs, $rdata, $cfg, $file);

	##backup config file
	if(-f "$self->{wrkDir}/named.conf"){
		my $file	= iMSCP::File->new( filename => "$self->{wrkDir}/named.conf" );
		$file->copyFile("$self->{bkpDir}/named.conf.".time) and return 1;
	} else {
		error("$self->{wrkDir}/named.conf not found. Run setup again to fix this");
		return 1;
	}

	# Loading all needed templates from /etc/imscp/bind/parts
	my ($bTag, $eTag);
	$bTag	= iMSCP::File->new(filename => "$self->{tplDir}/cfg_entry_b.tpl")->get();
	$eTag	= iMSCP::File->new(filename => "$self->{tplDir}/cfg_entry_e.tpl")->get();
	return 1 unless( $bTag && $eTag);

	# Preparation tags
	my $tags_hash	= { DMN_NAME => $option->{DMN_NAME} };

	$bTag	= process($tags_hash, $bTag);
	$eTag	= process($tags_hash, $eTag);

	# Loading working file from /etc/imscp/bind/working/named.conf
	$file	= iMSCP::File->new(filename => "$self->{wrkDir}/named.conf");
	$cfg	= $file->get();
	return 1 if (!$cfg);

	#delete
	$cfg = replaceBloc($bTag, $eTag, '', $cfg, undef);

	# Store the new builded file in the working directory
	$file = iMSCP::File->new(filename => "$self->{wrkDir}/named.conf");
	$file->set($cfg) and return 1;
	$file->save() and return 1;
	$file->mode(0644) and return 1;
	$file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'}) and return 1;

	# Install the new file in the production directory
	$file->copyFile($self::bindConfig{BIND_CONF_FILE}) and return 1;

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
