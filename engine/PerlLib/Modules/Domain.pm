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

package Modules::Domain;

use strict;
use warnings;
use iMSCP::Debug;

use vars qw/@ISA/;

@ISA = ('Common::SimpleClass');
use Common::SimpleClass;

sub _init{
	debug('Starting...');

	my $self		= shift;

	debug('Ending...');
	0;
}


sub loadData{
	debug('Starting...');

	use iMSCP::Dir;

	my $self	= shift;
	my $data	= shift;

	return 1 if !$data->{$self->{dmnId}};

	$self->{$_} = $data->{$self->{dmnId}}->{$_} for keys %{$data->{$self->{dmnId}}};

	debug('Ending...');
	0;
}

sub process{
	debug('Starting...');

	my $self		= shift;
	$self->{dmnId}	= shift;
	my $data		= shift;

	#use Data::Dumper;
	#error(Dumper($self->{dmnId}));

	my $rs = $self->loadData($data);
	#return $rs if $rs;
	#error(Dumper($self));

	my @sql;

	if($self->{domain_status} =~ /^toadd|change$/){

		$rs = $self->add();
		return $rs if $rs;

		@sql = (
			"UPDATE `domain` SET `domain_status` = ? WHERE `domain_id` = ?",
			'delete',
			$self->{domain_id}
		);

	}

	if($self->{domain_status} =~ /^delete$/){

		$rs = $self->delete();
		return $rs if $rs;

		@sql = ("DELETE FROM `domain` WHERE `domain_id` = ?", $self->{domain_id});
	}

	#my $database = iMSCP::Database->factory();
	#my $rdata = $database->doQuery('update', @sql);
	#error("$rdata") and return 1 if(ref $rdata ne 'HASH');

	debug('Ending...');
	0;
}

sub add{
	debug('Starting...');

	my $self		= shift;
	$self->{mode}	= 'add';
	my $rs;

	$rs = $self->prepareAdd();

	debug('Ending...');
	0;
}

sub delete{
	debug('Starting...');

	my $self		= shift;
	$self->{mode}	= 'delete';
	my $rs;

	for(qw/buildDNSData buildHTTPData buildMTAData/){
		$rs = eval "\$self->$_();";
		error("$@") if($@);
		return $rs if $rs;
	}

	@{$self->{Servers}}	= iMSCP::Dir->new(dirname => "$FindBin::Bin/PerlLib/Servers")->getFiles();
	@{$self->{Addons}}	= iMSCP::Dir->new(dirname => "$FindBin::Bin/PerlLib/Addons")->getFiles();

	$rs |= $self->runStep('preDelDmn',	'Servers');
	$rs |= $self->runStep('preDelDmn',	'Addons');
	$rs |= $self->runStep('delDomain', 	'Servers');
	$rs |= $self->runStep('delDomain',	'Addons');
	$rs |= $self->runStep('postDelDmn',	'Servers');
	$rs |= $self->runStep('postDelDmn',	'Addons');

	debug('Ending...');
	0;
}

sub prepareAdd{
	debug('Starting...');

	use  iMSCP::Dir;

	my $self		= shift;
	my $rs;

	for(qw/buildDNSData buildHTTPData buildMTAData/){
		$rs = eval "\$self->$_();";
		error("$@") if($@);
		return $rs if $rs;
	}

	@{$self->{Servers}}	= iMSCP::Dir->new(dirname => "$FindBin::Bin/PerlLib/Servers")->getFiles();
	@{$self->{Addons}}	= iMSCP::Dir->new(dirname => "$FindBin::Bin/PerlLib/Addons")->getFiles();

	$rs |= $self->runStep('preAddDmn',	'Servers');
	$rs |= $self->runStep('preAddDmn',	'Addons');
	$rs |= $self->runStep('addDomain', 	'Servers');
	$rs |= $self->runStep('addDomain',	'Addons');
	$rs |= $self->runStep('postAddDmn',	'Servers');
	$rs |= $self->runStep('postAddDmn',	'Addons');

	debug('Ending...');
	0;
}

sub runStep{
	debug('Starting...');

	my $self	= shift;
	my $func	= shift;
	my $type	= shift;
	my $rs		= 0;

	my ($file, $class, $instance);

	for (@{$self->{$type}}){
		s/\.pm//;
		$file	= "$type/$_.pm";
		$class	= "${type}::$_";
		require $file;
		$instance	= $class->factory();
		$rs |= $instance->$func($self->{$_})
				if(
					$instance->can($func)
					&&
					(exists $self->{$_} || $type eq 'Addons')
				);
	}

	debug('Ending...');
	$rs;
}

sub buildHTTPData{
	debug('Starting...');

	my $self	= shift;
	my $groupName	=
	my $userName	=
			$main::imscpConfig{SYSTEM_USER_PREFIX}.
			($main::imscpConfig{SYSTEM_USER_MIN_UID} + $self->{domain_admin_id});

	$self->{httpd} = {
		DMN_NAME					=> $self->{domain_name},
		DMN_IP						=> $self->{ip_number},
		WWW_DIR						=> "$main::imscpConfig{'USER_HOME_DIR'}/$userName",
		PEAR_DIR					=> $main::imscpConfig{'PEAR_DIR'},
		PHP_VERSION					=> $main::imscpConfig{'PHP_VERSION'},
		BASE_SERVER_VHOST_PREFIX	=> $main::imscpConfig{'BASE_SERVER_VHOST_PREFIX'},
		BASE_SERVER_VHOST			=> $main::imscpConfig{'BASE_SERVER_VHOST'},
		USER						=> $userName,
		GROUP						=> $groupName,
		MOUNT_POINT					=> $self->{domain_mount_point},
		have_php					=> $self->{have_php},
		have_cgi					=> $self->{have_cgi},
		BWLIMIT						=> $self->{bandwidth}
	};

	debug('Ending...');
	0;
}

sub buildMTAData{
	debug('Starting...');

	my $self	= shift;

	if(
		$self->{mode} ne 'add'
		||
		defined $self->{mail_on_domain} && $self->{mail_on_domain} > 0
		||
		defined $self->{mail_limit} && $self->{mail_limit} >=0
	){
		$self->{mta} = {
			DMN_NAME	=> $self->{domain_name},
		};
	} else {
		use Data::Dumper;
		error(Dumper($self->{mode} ne 'add').'a');
		error(Dumper(defined $self->{mail_on_domain} && $self->{mail_on_domain} > 0).'a');
		fatal(Dumper(defined $self->{mail_limit} && $self->{mail_limit} >=0 ).'a');
	}

	debug('Ending...');
	0;
}

sub buildDNSData{
	debug('Starting...');

	use iMSCP::Database;

	my $self	= shift;
	if($self->{mode} eq 'add'){
		my $sql = "
			SELECT
				*
			FROM
				`domain_dns`
			WHERE
				`domain_dns`.`alias_id` = ?
			AND
				`domain_dns`.`domain_id` = ?
			ORDER BY
				`domain_dns_id`
		";

		my $database = iMSCP::Database->factory();
		my $rdata = $database->doQuery('domain_dns_id', $sql, 0, $self->{domain_id});
		error("$rdata") and return 1 if(ref $rdata ne 'HASH');

		$self->{named}->{DMN_CUSTOM}->{$_} = $rdata->{$_} for keys %$rdata;
	}

	$self->{named}->{DMN_NAME}	= $self->{domain_name};
	$self->{named}->{DMN_IP}	= $self->{ip_number};

	debug('Ending...');
	0;
}
1;
