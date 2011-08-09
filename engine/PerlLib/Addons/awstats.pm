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
# @version		SVN: $Id: apache2.pm 4856 2011-07-11 08:48:54Z sci2tech $
# @link			http://i-mscp.net i-MSCP Home Site
# @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Addons::awstats;

use strict;
use warnings;
use iMSCP::Debug;

use vars qw/@ISA/;

@ISA = ('Common::SingletonClass');
use Common::SingletonClass;

sub factory{
	return Addons::awstats->new();
}

sub preinstall{
	debug('Starting...');

	use Addons::awstats::installer;

	my $self	= shift;
	my $rs		= Addons::awstats::installer->new()->registerHooks();

	debug('Ending...');
	$rs;
}

sub install{
	debug('Starting...');

	use Addons::awstats::installer;

	my $self = shift;
	my $rs = Addons::awstats::installer->new()->install();

	debug('Ending...');
	$rs;
}

sub preAddDmn{
	debug('Starting...');

	use Servers::httpd;

	my $self = shift;
	my $httpd = Servers::httpd->factory();

	my $rs = $httpd->registerPreHook(
		'buildConf', sub { return $self->awstatsSection(@_); }
	) if $httpd->can('registerPreHook');

	debug('Ending...');
	$rs;
}

sub awstatsSection{
	debug('Starting...');

	use iMSCP::Templator;
	use Servers::httpd;

	my $self = shift;
	my $data = shift;
	my $filename = shift;

	if($filename eq 'domain.tpl'){
		my ($bTag, $eTag);
		if($main::imscpConfig{AWSTATS_ACTIVE} ne 'yes'){
			$bTag = '# SECTION awstats support BEGIN.';
			$eTag = '# SECTION awstats support END.';
		} elsif($main::imscpConfig{AWSTATS_MODE} ne '1'){
			$bTag = '# SECTION awstats static BEGIN.';
			$eTag = '# SECTION awstats static END.';
		} else {
			$bTag = '# SECTION awstats dinamic BEGIN.';
			$eTag = '# SECTION awstats dinamic END.';
		}
		$data = replaceBloc($bTag, $eTag, '', $data, undef);
		my $tags = {
			AWSTATS_CACHE_DIR	=> $main::imscpConfig{AWSTATS_CACHE_DIR},
			AWSTATS_CONFIG_DIR	=> $main::imscpConfig{AWSTATS_CONFIG_DIR},
			AWSTATS_ENGINE_DIR	=> $main::imscpConfig{AWSTATS_ENGINE_DIR},
			AWSTATS_WEB_DIR		=> $main::imscpConfig{AWSTATS_WEB_DIR},
			AWSTATS_ROOT_DIR	=> $main::imscpConfig{AWSTATS_ROOT_DIR},
			AWSTATS_GROUP_AUTH	=> $main::imscpConfig{AWSTATS_GROUP_AUTH}
		};
		$data = process($tags, $data);

	} else {

		#register again for next file
		my $httpd = Servers::httpd->factory();
		my $rs = $httpd->registerPreHook(
			'buildConf', sub { return $self->awstatsSection(@_); }
		) if $httpd->can('registerPreHook');
	}

	debug('Ending...');
	$data;
}
1;
