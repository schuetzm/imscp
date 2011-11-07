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
# @author		Daniel Andreca <sci2tech@gmail.com>
# @version		SVN: $Id$
# @link			http://i-mscp.net i-MSCP Home Site
# @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2

#####################################################################################
# Package description:
#
# This package provides a class that is responsible to install all dependencies
# (libraries, tools and softwares) required by i-MSCP on Debian like operating systems.
#

package library::debian_autoinstall;

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
# @param self $self iMSCP::debian_autoinstall instance
# @return int 0
sub _init {
	debug('Starting...');

	my $self = shift;

	$self->{nonfree} = 'non-free';

	debug('Ending...');
	0;
}

# Process pre-build tasks.
#
# @param self $self iMSCP::debian_autoinstall instance
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

	$rs = $self->UpdateAptSourceList();
	return $rs if $rs;

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

	$rs = execute('apt-get update', \$stdout, \$stderr);
	debug("$stdout") if $stdout;
	error("$stderr") if $stderr;
	error('Unable to update package index from remote repository') if $rs && !$stderr;
	return $rs if $rs;

	debug('Ending...');
	0;
}

# Installs pre-required packages.
#
# @param self $self iMSCP::debian_autoinstall instance
# @return int 0 on success, other on failure
sub preRequish {
	debug('Starting...');

	my $self = shift;

	iMSCP::Dialog->factory()->infobox('Installing pre-required packages');

	my($rs, $stderr);

	$rs = execute('apt-get -y install dialog libxml-simple-perl', undef, \$stderr);
	error("$stderr") if $stderr;
	error('Unable to install pre-required packages.') if $rs && ! $stderr;
	return $rs if $rs;

	# Force dialog now
	iMSCP::Dialog->reset();

	debug('Ending...');
	0;
}

# Process apt source list.
#
# This subroutine parse the apt source list file to ensure presence of the non-free
# packages availability. If non-free section is not already enabled, this method try
# to find in on the remote repository and add it to the current Debian repository URI.
#
# @param self $self iMSCP::debian_autoinstall instance
# @return int 0 on success, other on failure
sub UpdateAptSourceList {
	debug('Starting...');

	my $self = shift;

	use iMSCP::File;

	iMSCP::Dialog->factory()->infobox('Processing apt sources list');

	my $file = iMSCP::File->new(filename => '/etc/apt/sources.list');

	$file->copyFile('/etc/apt/sources.list.bkp') unless( -f '/etc/apt/sources.list.bkp');
	my $content = $file->get();

	unless ($content){
		error('Unable to read /etc/apt/sources.list file');
		return 1;
	}

	my ($foundNonFree, $needUpdate, $rs, $stdout, $stderr);

	while($content =~ /^deb\s+(?<uri>(?:https?|ftp)[^\s]+)\s+(?<distrib>[^\s]+)\s+(?<components>.+)$/mg){
		my %repos = %+;

		# is non-free repository available?
		unless($repos{'components'} =~ /\s?$self->{nonfree}(\s|$)/ ){
			my $uri = "$repos{uri}/dists/$repos{distrib}/$self->{nonfree}/";
			$rs = execute("wget --spider $uri", \$stdout, \$stderr);
			debug("$stdout") if $stdout;
			debug("$stderr") if $stderr;

			unless ($rs){
				$foundNonFree = 1;
				debug("Enabling non free section on $repos{uri}");
				$content =~ s/^($&)$/$1 $self->{nonfree}/mg;
				$needUpdate = 1;
			}
		} else {
			debug("Non free section is already enabled on $repos{uri}");
			$foundNonFree = 1;
		}

	}

	unless($foundNonFree){
		error('Unable to found repository that support non-free packages');
		return 1;
	}

	if($needUpdate){
		$file->set($content);
		$file->save() and return 1;

		$rs = $self->updateSystemPackagesIndex();
		return $rs if $rs;
	}

	debug('Ending...');
	0;
}

# Install Debian packages list required by i-MSCP.
#
# @param self $self iMSCP::debian_autoinstall instance
# @return in 0 on success, other on failure
sub installPackagesList {
	debug('Starting...');

	my $self = shift;

	iMSCP::Dialog->factory()->infobox('Installing needed packages');

	my($rs, $stderr);

	$rs = execute("apt-get -y install $self->{toInstall}", undef, \$stderr);
	error("$stderr") if $stderr && $rs;
	error('Can not install packages.') if $rs && ! $stderr;
	return $rs if $rs;

	debug('Ending...');
	0;
}

# Enable service
# 
# @access public
# @param self $self iMSCP::debian_autoinstall instance
# @param string $service Name of service
# @return int
sub disableService {
	my $self = shift;
	my $fileName = shift;
	my $stdout = shift;
	my $stderr = shift;
	return execute("/usr/sbin/update-rc.d -f $fileName remove", $stdout, $stderr);
}

# Enable service
# 
# @access public
# @param self $self iMSCP::debian_autoinstall instance
# @param string $service Name of service
# @return int
sub enableService {
	my $self = shift;
	my $fileName = shift;
	my $stdout = shift;
	my $stderr = shift;
	return execute("/usr/sbin/update-rc.d $fileName defaults", $stdout, $stderr);
}

# Configure the resolver according to user's preferences
#
# @access public
# @param self $self iMSCP::debian_autoinstall instance
# @return int
sub setResolver {
	my $self = shift;
	my $stdout = shift;
	my $stderr = shift;

	my $file = iMSCP::File->new(filename => $main::imscpConfig{'RESOLVER_CONF_FILE'});
	my $content = $file->get();

	if (! $content){
		my $err = "Can't read $main::imscpConfig{'RESOLVER_CONF_FILE'}";
		error("$err");
		return 1;
	}

	if($main::imscpConfig{'LOCAL_DNS_RESOLVER'} =~ /yes/i) {
		if($content !~ /nameserver 127.0.0.1/i) {
			$content =~ s/(nameserver.*)/nameserver 127.0.0.1\n$1/i;
		}
	} else {
		$content =~ s/nameserver 127.0.0.1//i;
	}

	# Saving the old file if needed
	if(!-f "$main::imscpConfig{'RESOLVER_CONF_FILE'}.bkp") {
		$file->copyFile("$main::imscpConfig{'RESOLVER_CONF_FILE'}.bkp") and return 1;
	}

	# Storing the new file
	$file->set($content) and return 1;
	$file->save() and return 1;
	$file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'}) and return 1;
	$file->mode(0644) and return 1;

	0;
}


################################################################################
# Setup rkhunter - (Setup / Update)
#
# This subroutine process the following tasks:
#
# - update rkhunter database files (only during setup process)
# - Debian specific: Updates the configuration file and cron task, and
# remove default unreadable created log file
#
# @return int 0 on success, other on failure
#
sub setup_rkhunter {

	my ($rs, $rdata);

	# Deleting any existent log files
	my $file = iMSCP::File->new (filename => $main::imscpConfig{'RKHUNTER_LOG'});
	$file->set();
	$file->save() and return 1;
	$file->owner('root', 'adm');
	$file->mode(0644);

	# Updates the rkhunter configuration provided by Debian like distributions
	# to disable the default cron task (i-MSCP provides its own cron job for rkhunter)
	if(-e '/etc/default/rkhunter') {
		# Get the file as a string
		$file = iMSCP::File->new (filename => '/etc/default/rkhunter');
		$rdata = $file->get();
		return 1 if(!$rdata);

		# Disable cron task default
		$rdata =~ s/CRON_DAILY_RUN="(yes)?"/CRON_DAILY_RUN="no"/gmi;

		# Saving the modified file
		$file->set($rdata) and return 1;
		$file->save() and return 1;
	}

	# Updates the logrotate configuration provided by Debian like distributions
	# to modify rigts
	if(-e '/etc/logrotate.d/rkhunter') {
		# Get the file as a string
		$file = iMSCP::File->new (filename => '/etc/logrotate.d/rkhunter');
		$rdata = $file->get();
		return 1 if(!$rdata);

		# Disable cron task default
		$rdata =~ s/create 640 root adm/create 644 root adm/gmi;

		# Saving the modified file
		$file->set($rdata) and return 1;
		$file->save() and return 1;
	}

	# Update weekly cron task provided by Debian like distributions to avoid
	# creation of unreadable log file
	if(-e '/etc/cron.weekly/rkhunter') {
		# Get the rkhunter file content
		$file = iMSCP::File->new (filename => '/etc/cron.weekly/rkhunter');
		$rdata = $file->get();
		return 1 if(!$rdata);

		# Adds `--nolog`option to avoid unreadable log file
		$rdata =~ s/(--versioncheck\s+|--update\s+)(?!--nolog)/$1--nolog /g;

		# Saving the modified file
		$file->set($rdata) and return 1;
		$file->save() and return 1;
	}

	0;
}

1;
