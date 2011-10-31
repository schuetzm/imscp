#!/usr/bin/perl

# i-MSCP - internet Multi Server Control Panel
# Copyright 2010 - 2011 by internet Multi Server Control Panel
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
# @author		Marc Schütz <schuetzm@gmx.net>
# @version		SVN: $Id$
# @link			http://i-mscp.net i-MSCP Home Site
# @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2

#####################################################################################
# Package description:
#
# This package provides a class that is responsible to install all dependencies
# (libraries, tools and softwares) required by i-MSCP on openSuSE.
#

package library::suse_linux_autoinstall;

use strict;
use warnings;

use iMSCP::Debug;
use Symbol;
use iMSCP::Execute qw/execute/;
use iMSCP::Dialog;

use vars qw/@ISA/;
@ISA = ('Common::SingletonClass', 'library::common_autoinstall');
use Common::SingletonClass;
use library::common_autoinstall;

# Initializer.
#
# @param self $self iMSCP::suse_linux_autoinstall instance
# @return int 0
sub _init {
	debug('Starting...');

	my $self = shift;

	debug('Ending...');
	0;
}

# Process pre-build tasks.
#
# @param self $self iMSCP::suse_linux_autoinstall instance
# @return int 0 on success, other on failure
sub preBuild {
	debug('Starting...');

	my $self = shift;
	my $rs;

	$rs = $self->updateSystemPackagesIndex();
	return $rs if $rs;

	$rs = $self->preRequish();
	return $rs if $rs;

	$self->loadOldImscpConfigFile();

	$rs = $self->readPackagesList();
	return $rs if $rs;

	$rs = $self->installPackagesList();
	return $rs if $rs;

	debug('Ending...');
	0;
}

# Updates system packages index from remote repository.
#
# @return int 0 on success, other on failure
sub updateSystemPackagesIndex {
	debug('Starting...');

	iMSCP::Dialog->factory()->infobox('Updating system packages index');

	my ($rs, $stdout, $stderr);

	$rs = execute('zypper -n ref', \$stdout, \$stderr);
	debug("$stdout") if $stdout;
	error("$stderr") if $stderr;
	error('Unable to update package index from remote repository') if $rs && !$stderr;
	return $rs if $rs;

	debug('Ending...');
	0;
}

# Installs pre-required packages.
#
# @param self $self iMSCP::suse_linux_autoinstall instance
# @return int 0 on success, other on failure
sub preRequish {
	debug('Starting...');

	my $self = shift;

	iMSCP::Dialog->factory()->infobox('Installing pre-required packages');

	my($rs, $stderr);

	$rs = execute('zypper -n install dialog perl-XML-Simple make', undef, \$stderr);
	error("$stderr") if $stderr;
	error('Unable to install pre-required packages.') if $rs && ! $stderr;
	return $rs if $rs;

	my $SO = iMSCP::SO->new();
	my $files_dir = "$FindBin::Bin/docs/" . ucfirst($SO->{Distribution}) . "/files";

	foreach(
		"a2ensite",
		"a2dissite"
	) {
		$rs = execute("install -m 755 -o root -g root $files_dir/$_ /usr/sbin/$_", undef, \$stderr);
		error("Unable to install required file $_.") if $rs;
		return $rs if $rs;
	}

	$rs = execute("rpm -Uhv $files_dir/*.src.rpm", undef, \$stderr);
	error("Unable to install required src RPMs.") if $rs;
	return $rs if $rs;

	# Force dialog now
	iMSCP::Dialog->reset();

	debug('Ending...');
	0;
}

# Install openSuSE packages list required by i-MSCP.
#
# @param self $self iMSCP::suse_linux_autoinstall instance
# @return in 0 on success, other on failure
sub installPackagesList {
	debug('Starting...');

	my $self = shift;

	iMSCP::Dialog->factory()->infobox('Installing needed packages');

	my($rs, $stderr);

	$rs = execute("zypper -n install -- $self->{toInstall}", undef, \$stderr);
	error("$stderr") if $stderr && $rs;
	error('Can not install packages.') if $rs && ! $stderr;
	return $rs if $rs;

	debug('Ending...');
	0;
}

# Enable service
# 
# @access public
# @param self $self iMSCP::suse_linux_autoinstall instance
# @param string $service Name of service
# @return int
sub disableService {
	my $self = shift;
	my $fileName = shift;
	my $stdout = shift;
	my $stderr = shift;
	execute("/sbin/insserv -r -f $fileName", $stdout, $stderr);
}

# Enable service
# 
# @access public
# @param self $self iMSCP::suse_linux_autoinstall instance
# @param string $service Name of service
# @return int
sub enableService {
	my $self = shift;
	my $fileName = shift;
	my $stdout = shift;
	my $stderr = shift;
	execute("/sbin/insserv $fileName", $stdout, $stderr);
}

# Configure the resolver according to user's preferences
#
# @access public
# @param self $self iMSCP::suse_linux_autoinstall instance
# @return int
sub setResolver {
	my $self = shift;
	my $stdout = shift;
	my $stderr = shift;

	my $resolver;
	$resolver = ($main::imscpConfig{'LOCAL_DNS_RESOLVER'} =~ /yes/i) ? 'bind' : 'resolver';
	execute("/sbin/yast2 sysconfig set NETCONFIG_DNS_FORWARDER=$resolver", $stdout, $stderr);
}

1;
