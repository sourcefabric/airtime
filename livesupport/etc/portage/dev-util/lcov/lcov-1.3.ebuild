# Copyright 1999-2005 Gentoo Foundation
# Distributed under the terms of the GNU General Public License v2
# $Header$

inherit eutils

DESCRIPTION="LCOV is an extension to GCOV, providing HTML output and support for
large projects"
HOMEPAGE="http://ltp.sourceforge.net/coverage/lcov.php"
SRC_URI="mirror://sourceforge/ltp/${P}.tar.gz"

LICENSE="LGPL-2.1"
SLOT="0"
KEYWORDS="amd64 ppc sparc x86"

RESTRICT="maketest"

DEPEND="dev-lang/perl"

src_unpack() {
	unpack ${A}
	cd ${S}
	# see https://sourceforge.net/tracker/index.php?func=detail&aid=1292850&group_id=3382&atid=303382
	epatch ${FILESDIR}/lcov-1.3-geninfo-regexp.patch
	# see https://sourceforge.net/tracker/index.php?func=detail&aid=1292854&group_id=3382&atid=303382
	epatch ${FILESDIR}/lcov-1.3-install-to-prefix.patch
}

src_compile() {
	emake || die
	#make check || die
}

src_install() {
	# by setting PREFIX we can tell the Makefile where to install
	export PREFIX=${D}/usr
	emake install || die
	dodoc CHANGES README
}
