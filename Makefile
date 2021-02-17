# $FreeBSD$

PORTNAME=	pfSense-pkg-openvpn-multihop
PORTVERSION=	0.1
PORTREVISION=	10
CATEGORIES=	security
MASTER_SITES=	# empty	
DISTFILES=	# empty
EXTRACT_ONLY=	# empty

MAINTAINER=	dev@daemonbytes.net
COMMENT=	pfSense package openvpn-multihop

LICENSE=	APACHE20

#RUN_DEPENDS=	openvpn>=2.4.3:security/openvpn 

NO_BUILD=	yes
NO_MTREE=	yes

SUB_FILES=	pkg-install pkg-deinstall
SUB_LIST=	PORTNAME=${PORTNAME}

do-extract:
	${MKDIR} ${WRKSRC}

do-install:
	${MKDIR} ${STAGEDIR}${PREFIX}/pkg
	${MKDIR} ${STAGEDIR}${PREFIX}/www
	${MKDIR} ${STAGEDIR}${PREFIX}/etc/openvpn-multihop
	${MKDIR} ${STAGEDIR}${DATADIR}
	${INSTALL_DATA} -m 0644 ${FILESDIR}${PREFIX}/pkg/openvpn-client-multihop.xml \
		${STAGEDIR}${PREFIX}/pkg
	${INSTALL_DATA} -m 0750 ${FILESDIR}${PREFIX}/etc/openvpn-multihop/addroute.sh  \
		${STAGEDIR}${PREFIX}/etc/openvpn-multihop
	${INSTALL_DATA} ${FILESDIR}${PREFIX}/pkg/openvpn-client-multihop.inc \
		${STAGEDIR}${PREFIX}/pkg
	${INSTALL_DATA} ${FILESDIR}${PREFIX}/www/vpn_openvpn_multihop.php \
		${STAGEDIR}${PREFIX}/www
	${INSTALL_DATA} ${FILESDIR}${DATADIR}/info.xml \
		${STAGEDIR}${DATADIR}
	@${REINPLACE_CMD} -i '' -e "s|%%PKGVERSION%%|${PKGVERSION}|" \
		${STAGEDIR}${DATADIR}/info.xml

.include <bsd.port.mk>
