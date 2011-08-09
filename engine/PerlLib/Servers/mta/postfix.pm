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

package Servers::mta::postfix;

use strict;
use warnings;
use iMSCP::Debug;
use Data::Dumper;

use vars qw/@ISA/;

@ISA = ('Common::SingletonClass');
use Common::SingletonClass;

sub _init{
	my $self	= shift;

	debug('Starting...');

	$self->{cfgDir} = "$main::imscpConfig{'CONF_DIR'}/postfix";
	$self->{bkpDir} = "$self->{cfgDir}/backup";
	$self->{wrkDir} = "$self->{cfgDir}/working";

	$self->{commentChar} = '#';

	#$self->{$_} = $main::imscpConfig{$_} foreach(keys %main::imscpConfig);

	tie %self::postfixConfig, 'iMSCP::Config','fileName' => "$self->{cfgDir}/postfix.data";
	$self->{$_} = $self::postfixConfig{$_} foreach(keys %self::postfixConfig);

	debug('Ending...');
	0;
}

sub preinstall{
	debug('Starting...');

	use Servers::mta::postfix::installer;

	my $self	= shift;
	my $rs		= Servers::mta::postfix::installer->preinstall();

	debug('Ending...');
	$rs;
}

sub install{
	debug('Starting...');

	use Servers::mta::postfix::installer;

	my $self	= shift;
	my $rs		= Servers::mta::postfix::installer->new()->install();

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

sub setEnginePermissions{
	debug('Starting...');

	use Servers::httpd::apache::installer;

	my $self	= shift;
	my $rs = Servers::mta::postfix::installer->new()->setEnginePermissions();

	debug('Ending...');
	$rs;
}

sub registerPreHook{
	debug('Starting...');

	my $self		= shift;
	my $fname		= shift;
	my $callback	= shift;

	my $installer	= Servers::mta::postfix::installer->new();

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

	my $installer	= Servers::mta::postfix::installer->new();

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
	$rs = execute("$self->{CMD_MTA} restart", \$stdout, \$stderr);
	debug("$stdout") if $stdout;
	error("$stderr") if $stderr;
	return $rs if $rs;

	debug('Ending...');
	0;
}

sub addDomain{
	debug('Starting...');

	my $self = shift;
	my $data = shift;

	error(Dumper($data).'');

	foreach(keys %{$errmsg}){
		error("$errmsg->{$_}") unless $data->{$_};
		return 1 unless $data->{$_};
	}

	my $entry = "$data->{DMN_NAME}\t\t\tvdmn_entry\n";

	iMSCP::File->new(
		filename => $self->{MTA_VIRTUAL_DMN_HASH}
	)->copyFile( "$self->{bkpDir}/domains".time ) and return 1;

	my $file	= iMSCP::File->new( filename => "$self->{wrkDir}/domains");
	my $content	= $file->get() || return 1;

	$content .= $entry unless $content =~ /$entry/mg;

	$file->set($content);
	$file->save() and return 1;
	$file->mode(0644) and return 1;
	$file->owner(
			$main::imscpConfig{'ROOT_USER'},
			$main::imscpConfig{'ROOT_GROUP'}
	) and return 1;
	$file->copyFile( $self->{MTA_VIRTUAL_DMN_HASH} ) and return 1;


	debug('Ending...');
	0;
}

1;
