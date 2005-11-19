# Copyright 1999-2005 Gentoo Foundation
# Distributed under the terms of the GNU General Public License v2
# $Header$

inherit eutils flag-o-matic

DESCRIPTION="XmlRpc++ is a C++ implementation of the XML-RPC protocol."
SRC_URI="mirror://sourceforge/xmlrpcpp/${PN}${PV}.tar.gz"
HOMEPAGE="http://xmlrpcpp.sourceforge.net/"
LICENSE="LGPL"

DEPEND=""
KEYWORDS="~x86 ~ppc ~hppa ~alpha ~amd64"
IUSE=""
SLOT=0

src_unpack() {
	unpack ${A}
	cd ${PN}${PV}

	# this patch makes the source up-to-date with that of the CVS version
	# of 2004.07.13
	epatch ${FILESDIR}/xmlrpc++-0.7-to-cvs-20040713.patch
	# see http://sourceforge.net/tracker/index.php?func=detail&aid=990356&group_id=70654&atid=528555
	epatch ${FILESDIR}/xmlrpc++-automake.patch
	# see http://sourceforge.net/tracker/index.php?func=detail&aid=990676&group_id=70654&atid=528555
	epatch ${FILESDIR}/uninitialised_XmlRpcSource_ssl_ssl.patch
	# see http://sourceforge.net/tracker/?group_id=70654&atid=528555&func=detail&aid=1085119
	epatch ${FILESDIR}/incorrect_XmlRpcValue_struct_tm_conversion.patch

	sh autogen.sh
}

src_compile() {
	cd ${WORKDIR}/${PN}${PV}

	econf || die "econf failed"
	emake || die "emake failed"
}

src_install () {
	cd ${WORKDIR}/${PN}${PV}

	dodoc COPYING README.html
	emake prefix=${D} \
	      includedir=${D}/usr/include \
	      libdir=${D}/usr/lib \
		  DOC_DIR=${D}/usr/share/doc/${PF} \
		  install \
		|| die "make install failed"
}

