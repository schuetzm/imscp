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
# This package provides common routines for the distribution specific
# auto installers.
#

package library::common_autoinstall;

use strict;
use warnings;

use iMSCP::Debug;
use Symbol;
use iMSCP::Execute qw/execute/;
use iMSCP::Dialog;

use vars qw/@ISA/;
@ISA = ('Common::SingletonClass');
use Common::SingletonClass;

# Load old i-MSCP main configuration file.
#
# @return int 0
sub loadOldImscpConfigFile {

	debug('Starting...');

	use iMSCP::Config;

	$main::imscpConfigOld = {};

	my $oldConf = "$main::defaultConf{'CONF_DIR'}/imscp.old.conf";

	tie %main::imscpConfigOld, 'iMSCP::Config', 'fileName' => $oldConf, noerrors => 1 if (-f $oldConf);

	debug('Ending...');
	0;
}

# Reads packages list to be installed.
#
# @param self $self iMSCP::common_autoinstall instance
# @return int 0 on success, other on failure
sub readPackagesList {
	debug('Starting...');

	my $self = shift;
	my $SO = iMSCP::SO->new();
	my $confile = "$FindBin::Bin/docs/" . ucfirst($SO->{Distribution}) . "/" .
		lc($SO->{Distribution}) . "-packages-" . lc($SO->{CodeName}) . ".xml";

	fatal(ucfirst($SO->{Distribution})." $SO->{CodeName} is not supported!") if (! -f  $confile);

	eval "use XML::Simple";

	fatal('Unable to load perl module XML::Simple...') if($@);

	my $xml = XML::Simple->new(NoEscape => 1);
	my $data = eval { $xml->XMLin($confile, KeyAttr => 'name') };

	foreach(keys %{$data}){
		if(ref($data->{$_}) eq 'ARRAY'){
			$self->_parseArray($data->{$_});
		} else {
			if($data->{$_}->{alternative}){
				my $server  = $_;
				my @alternative = keys %{$data->{$server}->{alternative}};

				for (my $index = $#alternative; $index >= 0; --$index ){
					my $defServer = $alternative[$index];
					my $oldServer = $main::imscpConfigOld{uc($server) . '_SERVER'};

					if($@){
						error("$@");
						return 1;
					}

					if($oldServer && $defServer eq $oldServer){
						splice @alternative, $index, 1 ;
						unshift(@alternative, $defServer);
						last;
					}
				}

				my $rs;

				do{
					$rs = iMSCP::Dialog->factory()->radiolist(
						"Choose server $server",
						@alternative,
						#uncoment after dependicies check is implemented
						#'Not Used'
					);
				} while (!$rs);

				$self->{userSelection}->{$server} = lc($rs) eq 'not used' ? 'no' : $rs;

				foreach(@alternative){
					delete($data->{$server}->{alternative}->{$_}) if($_ ne $rs);
				}
			}

			$self->_parseHash($data->{$_});
		}
	};

	debug('Ending...');
	0;
}

# Perform post-build tasks.
#
# @param self $self iMSCP::common_autoinstall instance
# @return in 0 on success, other on failure
sub postBuild {
	debug('Starting...');

	my $self = shift;

	my $x = qualify_to_ref("SYSTEM_CONF", 'main');

	my $nextConf = $$$x . '/imscp.conf';
	tie %main::nextConf, 'iMSCP::Config', 'fileName' => $nextConf;

	$main::nextConf{uc($_) . "_SERVER"} = lc($self->{userSelection}->{$_}) foreach(keys %{$self->{userSelection}});

	debug('Ending...');
	0;
}

# Trim a string.
#
# @access private
# @param string $var String to be trimmed
# @return string
sub _trim {
	my $var = shift;
	$var =~ s/^\s+//;
	$var =~ s/\s+$//;
	$var;
}

# Parse hash.
#
# @access private
# @param self $self iMSCP::common_autoinstall instance
# @param HASH $hash Hash to be parsed
# @return void
sub _parseHash {
	my $self = shift;
	my $hash = shift;

	foreach(values %{$hash}) {
		if(ref($_) eq 'HASH') {
			$self->_parseHash($_);
		} elsif(ref($_) eq 'ARRAY') {
			$self->_parseArray($_);
		} else {
			$self->{toInstall} .= " " . _trim($_);
		}
	}
}

# Parse array
#
# @access private
# @param self $self iMSCP::common_autoinstall instance
# @param ARRAY $array Array to be parsed
# @return void
sub _parseArray {
	my $self = shift;
	my $array = shift;

	foreach(@{$array}){
		if(ref($_) eq 'HASH') {
			$self->_parseHash($_);
		}elsif(ref($_) eq 'ARRAY') {
			$self->_parseArray($_);
		} else {
			$self->{toInstall} .= " " . _trim($_);
		}
	}
}

1;
