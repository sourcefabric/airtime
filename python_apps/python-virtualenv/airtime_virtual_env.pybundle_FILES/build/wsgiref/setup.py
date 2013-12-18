#!/usr/bin/env python

"""Distutils setup file"""

import ez_setup
ez_setup.use_setuptools()

from setuptools import setup, find_packages

# Metadata
PACKAGE_NAME = "wsgiref"
PACKAGE_VERSION = "0.1.2"

setup(
    name=PACKAGE_NAME,
    version=PACKAGE_VERSION,

    description="WSGI (PEP 333) Reference Library",
    author="Phillip J. Eby",
    author_email="web-sig@python.org",
    license="PSF or ZPL",

    url="http://cheeseshop.python.org/pypi/wsgiref",

    long_description = open('README.txt').read(),
    test_suite  = 'test_wsgiref',
    packages    = ['wsgiref'],
)

