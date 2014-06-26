# -*- coding: utf-8 -*-
from unittest import TestCase
import httplib
import poster
import urllib2, urllib
import threading, time, signal
import sys
import os
import subprocess
import tempfile

port = 5123

class TestStreaming(TestCase):
    disable_https = True
    def setUp(self):
        self.opener = poster.streaminghttp.register_openers()
        if self.disable_https:
            # Disable HTTPS support for these tests to excercise the non-https code
            # HTTPS is tested in test_streaming_https.py
            if hasattr(httplib, "HTTPS"):
                self.https = getattr(httplib, "HTTPS")
                delattr(httplib, "HTTPS")
                reload(poster.streaminghttp)
            else:
                self.https = None
        else:
            self.https = None

        # Hard code to python2.6 for now, since python2.7 can't run the test server reliably
        cmd = ["python2.6", os.path.join(os.path.dirname(__file__), 'test_server.py'), str(port)]
        if not self.disable_https:
            cmd.append("ssl")
        null = open(os.devnull, "w")
        self.server_output = tempfile.TemporaryFile()
        self.server_proc = None
        try:
            self.server_proc = subprocess.Popen(cmd, stdout=self.server_output, stderr=self.server_output, close_fds=True)
            for i in range(20):
                try:
                    if self.disable_https:
                        urllib2.urlopen("http://localhost:%i/" % port).read()
                    else:
                        urllib2.urlopen("https://localhost:%i/" % port).read()
                    time.sleep(0.1)
                    break
                except:
                    #import traceback
                    #traceback.print_exc()
                    time.sleep(0.1)
            else:
                self.server_output.seek(0)
                print self.server_output.read()
                raise OSError("Error starting server")
        except:
            if self.server_proc:
                os.kill(self.server_proc.pid, signal.SIGINT)
                self.server_proc.wait()
            raise

    def tearDown(self):
        if self.https:
            setattr(httplib, "HTTPS", self.https)

        os.kill(self.server_proc.pid, signal.SIGINT)
        self.server_proc.wait()
        self.server_output.seek(0)
        print self.server_output.read()

    def _open(self, url, params=None, headers=None):
        try:
            if headers is None:
                headers = {}
            req = urllib2.Request("http://localhost:%i/%s" % (port, url), params,
                    headers)
            return urllib2.urlopen(req).read()
        except:
            self._opened = False
            raise

    def test_basic(self):
        response = self._open("testing123")

        self.assertEqual(response, "Path: /testing123")

    def test_basic2(self):
        response = self._open("testing?foo=bar")

        self.assertEqual(response, "Path: /testing\nfoo: bar")

    def test_nonstream_uploadfile(self):
        datagen, headers = poster.encode.multipart_encode([
            poster.encode.MultipartParam.from_file("file", __file__),
            poster.encode.MultipartParam("foo", "bar")])

        data = "".join(datagen)

        response = self._open("upload", data, headers)
        self.assertEqual(response, "Path: /upload\nfile: %s\nfoo: bar" % open(__file__).read())

    def test_stream_upload_generator(self):
        datagen, headers = poster.encode.multipart_encode([
            poster.encode.MultipartParam.from_file("file", __file__),
            poster.encode.MultipartParam("foo", "bar")])

        response = self._open("upload", datagen, headers)
        self.assertEqual(response, "Path: /upload\nfile: %s\nfoo: bar" % open(__file__).read())

    def test_stream_upload_file(self):
        data = open("poster/__init__.py")
        headers = {"Content-Length": str(os.path.getsize("poster/__init__.py"))}

        response = self._open("upload", data, headers)
        self.assertEquals(response, "Path: /upload\n%s" % open("poster/__init__.py").read().replace(" = ", " :  "))

    def test_stream_upload_file_no_len(self):
        data = open(__file__)
        self.assertRaises(ValueError, self._open, "upload", data, {})

    def test_stream_upload_generator_no_len(self):
        def data():
            yield ""

        self.assertRaises(ValueError, self._open, "upload", data(), {})

    def test_redirect(self):
        response = self._open("redirect")

        self.assertEqual(response, "Path: /foo")

    def test_login(self):
        password_manager = urllib2.HTTPPasswordMgrWithDefaultRealm()
        password_manager.add_password(
                None, "http://localhost:%i/needs_auth" % port, 'john', 'secret'
        )

        auth_handler = urllib2.HTTPBasicAuthHandler(password_manager)
        auth_handler.handler_order = 0

        self.opener.add_handler(auth_handler)

        data = open("poster/__init__.py")
        headers = {"Content-Length": str(os.path.getsize("poster/__init__.py"))}

        response = self._open("needs_auth", data, headers)
        self.assertEqual(response, "Path: /needs_auth\n%s" % open("poster/__init__.py").read().replace(" = ", " :  "))

class TestStreamingHTTPS(TestStreaming):
    disable_https = False
    def _open(self, url, params=None, headers=None):
        try:
            if headers is None:
                headers = {}
            req = urllib2.Request("https://localhost:%i/%s" % (port, url), params,
                    headers)
            return urllib2.urlopen(req).read()
        except:
            self._opened = False
            raise
