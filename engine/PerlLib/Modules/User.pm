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

package Modules::User;

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

	my $sql = "
				SELECT
					*
				FROM
					`admin`
				WHERE
					`admin_id` = ?
			";

	my $database = iMSCP::Database->factory();
	my $rdata = $database->doQuery('admin_id', $sql, $self->{usrId});

	error("$rdata") and return 1 if(ref $rdata ne 'HASH');
	error("No such user $self->{usrId}") and return 1 unless(exists $rdata->{$self->{usrId}});

	$self->{$_} = $rdata->{$self->{usrId}}->{$_} for keys %{$rdata->{$self->{usrId}}};

	debug('Ending...');
	0;
}

sub process{
	debug('Starting...');

	my $self		= shift;
	$self->{usrId}	= shift;

	my $rs = $self->loadData();
	return $rs if $rs;

	use Data::Dumper;
	debug(Dumper($self));

	my @sql;

	if($self->{admin_status} =~ /^toadd$/){

		$rs = $self->add();
		return $rs if $rs;

		@sql = (
			"UPDATE `admin` SET `admin_status` = ? WHERE `admin_id` = ?",
			'ok',
			$self->{admin_id}
		);

	}

	if($self->{admin_status} =~ /^delete$/){

		$rs = $self->delete();
		return $rs if $rs;

		@sql = ("DELETE FROM `admin` WHERE `admin_id` = ?", $self->{admin_id});
	}

	#my $database = iMSCP::Database->factory();
	#my $rdata = $database->doQuery('update', @sql);
	#error("$rdata") and return 1 if(ref $rdata ne 'HASH');

	debug('Ending...');
	0;
}

sub add{
	debug('Starting...');

	use Modules::SystemGroup;
	use Modules::SystemUser;
	use iMSCP::Rights;
	use iMSCP::Dir;
	use Servers::httpd;

	my $self = shift;

	error('Data not defined') if ! $self->{admin_id};
	return 1  if ! $self->{admin_id};

	my $rs;

	my $groupName	=
	my $userName	=
			$main::imscpConfig{SYSTEM_USER_PREFIX}.
			($main::imscpConfig{SYSTEM_USER_MIN_UID} + $self->{admin_id});

	$rs = Modules::SystemGroup->new()->addSystemGroup($groupName);
	return $rs if $rs;

	my $user = Modules::SystemUser->new();
	$user->{comment}	= "iMSCP virtual user";
	$user->{group}		= $groupName;
	$user->{shell} 		= '/bin/false';
	$rs = $user->addSystemUser($userName);
	return $rs if $rs;

	my $httpdGroup = (
		Servers::httpd->factory()->can('getRunningGroup')
		?
		Servers::httpd->factory()->getRunningGroup()
		:
		'-1'
	);
	my $rootUser	= $main::imscpConfig{ROOT_USER};
	my $rootGroup	= $main::imscpConfig{ROOT_GROUP};

	$rs = iMSCP::Dir->new(
		dirname => "$main::imscpConfig{'USER_HOME_DIR'}/$userName"
	)->make({
			mode	=> 0750,
			user	=> $userName,
			group	=> $httpdGroup
	});


	$rs = $self->oldEngineCompatibility();


	$rs = iMSCP::Dir->new(
		dirname => "$main::imscpConfig{'USER_HOME_DIR'}/$userName/logs"
	)->make({
			mode	=> 0750,
			user	=> $userName,
			group	=> $groupName
	});

	$rs = iMSCP::Dir->new(
		dirname => "$main::imscpConfig{'USER_HOME_DIR'}/$userName/backups"
	)->make({
			mode	=> 0755,
			user	=> $rootUser,
			group	=> $rootGroup
	});

	debug('Ending...');
	0;
}

sub delete{
	debug('Starting...');

	use Modules::SystemGroup;
	use Modules::SystemUser;
	use iMSCP::Rights;

	my $self = shift;

	error('Data not defined') if ! $self->{admin_id};
	return 1  if ! $self->{admin_id};

	my $rs;
	my $userName	=
			$main::imscpConfig{SYSTEM_USER_PREFIX}.
			($main::imscpConfig{SYSTEM_USER_MIN_UID} + $self->{admin_id});

	my $user = Modules::SystemUser->new();
	$user->{force} = 'yes';
	$rs = $user->delSystemUser($userName);
	return $rs if $rs;

	debug('Ending...');
	0;
}

sub oldEngineCompatibility{
	debug('Starting...');

	use iMSCP::Rights;
	use iMSCP::Execute;

	my $self = shift;

	if(-d "$main::imscpConfig{'USER_HOME_DIR'}/$self->{admin_name}"){

		my ($rs, $stdout, $stderr);
		my $groupName	=
		my $userName	= $main::imscpConfig{SYSTEM_USER_PREFIX}.
					($main::imscpConfig{SYSTEM_USER_MIN_UID} + $self->{admin_id});
		my $oldHome = "$main::imscpConfig{'USER_HOME_DIR'}/$self->{admin_name}";
		my $newHome = "$main::imscpConfig{'USER_HOME_DIR'}/$userName";

		$rs = execute("mv $oldHome $newHome", \$stdout, \$stderr);
		debug("$stdout") if $stdout;
		error("$stderr") if $stderr;
		error("Can not recover old home") if !$stderr && $rs;
		return $rs if $rs;

		$rs = setRights(
			"$newHome/$self->{admin_name}",
			{
				user	=> $userName,
				group	=> $groupName,
				recursive => 'yes'
			}
		);
		return $rs if $rs;

	}

	debug('Ending...');
	0;
}
1;
