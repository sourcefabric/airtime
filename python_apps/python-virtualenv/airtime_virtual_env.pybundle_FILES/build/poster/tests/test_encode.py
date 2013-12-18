# -*- coding: utf-8 -*-
from unittest import TestCase
import mimetypes
import poster.encode
import StringIO
import sys

def unix2dos(s):
    return s.replace("\n", "\r\n")

class TestEncode_String(TestCase):
    def test_simple(self):
        expected = unix2dos("""--XXXXXXXXX
Content-Disposition: form-data; name="foo"
Content-Type: text/plain; charset=utf-8

bar
""")
        self.assertEqual(expected,
                poster.encode.encode_string("XXXXXXXXX", "foo", "bar"))

    def test_quote_name_space(self):
        expected = unix2dos("""--XXXXXXXXX
Content-Disposition: form-data; name="foo baz"
Content-Type: text/plain; charset=utf-8

bar
""")
        self.assertEqual(expected,
                poster.encode.encode_string("XXXXXXXXX", "foo baz", "bar"))

    def test_quote_name_phparray(self):
        expected = unix2dos("""--XXXXXXXXX
Content-Disposition: form-data; name="files[]"
Content-Type: text/plain; charset=utf-8

bar
""")
        self.assertEqual(expected,
                poster.encode.encode_string("XXXXXXXXX", "files[]", "bar"))

    def test_quote_unicode_name(self):
        expected = unix2dos("""--XXXXXXXXX
Content-Disposition: form-data; name="=?utf-8?b?4piD?="
Content-Type: text/plain; charset=utf-8

bar
""")
        self.assertEqual(expected,
                poster.encode.encode_string("XXXXXXXXX", u"\N{SNOWMAN}", "bar"))

    def test_quote_value(self):
        expected = unix2dos("""--XXXXXXXXX
Content-Disposition: form-data; name="foo"
Content-Type: text/plain; charset=utf-8

bar baz@bat
""")
        self.assertEqual(expected,
                poster.encode.encode_string("XXXXXXXXX", "foo", "bar baz@bat"))

    def test_boundary(self):
        expected = unix2dos("""--ABC+DEF
Content-Disposition: form-data; name="foo"
Content-Type: text/plain; charset=utf-8

bar
""")
        self.assertEqual(expected,
                poster.encode.encode_string("ABC DEF", "foo", "bar"))

    def test_unicode(self):
        expected = unix2dos("""--XXXXXXXXX
Content-Disposition: form-data; name="foo"
Content-Type: text/plain; charset=utf-8

b\xc3\xa1r
""")
        self.assertEqual(expected,
                poster.encode.encode_string("XXXXXXXXX", "foo", u"bár"))


class TestEncode_File(TestCase):
    def test_simple(self):
        expected = unix2dos("""--XXXXXXXXX
Content-Disposition: form-data; name="foo"
Content-Type: text/plain; charset=utf-8

""")
        self.assertEqual(expected,
                poster.encode.encode_file_header("XXXXXXXXX", "foo", 42))

    def test_content_type(self):
        expected = unix2dos("""--XXXXXXXXX
Content-Disposition: form-data; name="foo"
Content-Type: text/html

""")
        self.assertEqual(expected,
                poster.encode.encode_file_header("XXXXXXXXX", "foo", 42, filetype="text/html"))

    def test_filename_simple(self):
        expected = unix2dos("""--XXXXXXXXX
Content-Disposition: form-data; name="foo"; filename="test.txt"
Content-Type: text/plain; charset=utf-8

""")
        self.assertEqual(expected,
                poster.encode.encode_file_header("XXXXXXXXX", "foo", 42,
                    "test.txt"))

    def test_quote_filename(self):
        expected = unix2dos("""--XXXXXXXXX
Content-Disposition: form-data; name="foo"; filename="test file.txt"
Content-Type: text/plain; charset=utf-8

""")
        self.assertEqual(expected,
                poster.encode.encode_file_header("XXXXXXXXX", "foo", 42,
                    "test file.txt"))

        expected = unix2dos("""--XXXXXXXXX
Content-Disposition: form-data; name="foo"; filename="test\\"file.txt"
Content-Type: text/plain; charset=utf-8

""")
        self.assertEqual(expected,
                poster.encode.encode_file_header("XXXXXXXXX", "foo", 42,
                    "test\"file.txt"))

    def test_unicode_filename(self):
        expected = unix2dos("""--XXXXXXXXX
Content-Disposition: form-data; name="foo"; filename="&#9731;.txt"
Content-Type: text/plain; charset=utf-8

""")
        self.assertEqual(expected,
                poster.encode.encode_file_header("XXXXXXXXX", "foo", 42,
                    u"\N{SNOWMAN}.txt"))

class TestEncodeAndQuote(TestCase):
    def test(self):
        self.assertEqual("foo+bar", poster.encode.encode_and_quote("foo bar"))
        self.assertEqual("foo%40bar", poster.encode.encode_and_quote("foo@bar"))
        self.assertEqual("%28%C2%A9%29+2008",
                poster.encode.encode_and_quote(u"(©) 2008"))

class TestMultiparam(TestCase):
    def test_from_params(self):
        fp = open("tests/test_encode.py")
        expected = [poster.encode.MultipartParam("foo", "bar"),
                    poster.encode.MultipartParam("baz", fileobj=fp,
                        filename=fp.name,
                        filetype=mimetypes.guess_type(fp.name)[0])]

        self.assertEqual(poster.encode.MultipartParam.from_params(
            [("foo", "bar"), ("baz", fp)]), expected)

        self.assertEqual(poster.encode.MultipartParam.from_params(
            (("foo", "bar"), ("baz", fp))), expected)

        self.assertEqual(poster.encode.MultipartParam.from_params(
            {"foo": "bar", "baz": fp}), expected)

        self.assertEqual(poster.encode.MultipartParam.from_params(
            [expected[0], expected[1]]), expected)

    def test_from_params_dict(self):

        p = poster.encode.MultipartParam('file', fileobj=open("tests/test_encode.py"))
        params = {"foo": "bar", "file": p}

        expected = [poster.encode.MultipartParam("foo", "bar"), p]
        retval = poster.encode.MultipartParam.from_params(params)

        expected.sort()
        retval.sort()

        self.assertEqual(retval, expected)

    def test_from_params_assertion(self):
        p = poster.encode.MultipartParam('file', fileobj=open("tests/test_encode.py"))
        params = {"foo": "bar", "baz": p}

        self.assertRaises(AssertionError, poster.encode.MultipartParam.from_params,
                params)

    def test_simple(self):
        p = poster.encode.MultipartParam("foo", "bar")
        boundary = "XYZXYZXYZ"
        expected = unix2dos("""--XYZXYZXYZ
Content-Disposition: form-data; name="foo"
Content-Type: text/plain; charset=utf-8

bar
--XYZXYZXYZ--
""")
        self.assertEqual(p.encode(boundary), expected[:-len(boundary)-6])
        self.assertEqual(p.get_size(boundary), len(expected)-len(boundary)-6)
        self.assertEqual(poster.encode.get_body_size([p], boundary),
                len(expected))
        self.assertEqual(poster.encode.get_headers([p], boundary),
                {'Content-Length': str(len(expected)),
                 'Content-Type': 'multipart/form-data; boundary=%s' % boundary})

        datagen, headers = poster.encode.multipart_encode([p], boundary)
        self.assertEqual(headers, {'Content-Length': str(len(expected)),
                 'Content-Type': 'multipart/form-data; boundary=%s' % boundary})
        self.assertEqual("".join(datagen), expected)

    def test_multiple_keys(self):
        params = poster.encode.MultipartParam.from_params(
                [("key", "value1"), ("key", "value2")])
        boundary = "XYZXYZXYZ"
        datagen, headers = poster.encode.multipart_encode(params, boundary)
        encoded = "".join(datagen)

        expected = unix2dos("""--XYZXYZXYZ
Content-Disposition: form-data; name="key"
Content-Type: text/plain; charset=utf-8

value1
--XYZXYZXYZ
Content-Disposition: form-data; name="key"
Content-Type: text/plain; charset=utf-8

value2
--XYZXYZXYZ--
""")
        self.assertEqual(encoded, expected)


    def test_stringio(self):
        fp = StringIO.StringIO("file data")
        params = poster.encode.MultipartParam.from_params( [("foo", fp)] )
        boundary = "XYZXYZXYZ"
        datagen, headers = poster.encode.multipart_encode(params, boundary)
        encoded = "".join(datagen)

        expected = unix2dos("""--XYZXYZXYZ
Content-Disposition: form-data; name="foo"
Content-Type: text/plain; charset=utf-8

file data
--XYZXYZXYZ--
""")
        self.assertEqual(encoded, expected)

    def test_reset_string(self):
        p = poster.encode.MultipartParam("foo", "bar")
        boundary = "XYZXYZXYZ"

        datagen, headers = poster.encode.multipart_encode([p], boundary)

        expected = unix2dos("""--XYZXYZXYZ
Content-Disposition: form-data; name="foo"
Content-Type: text/plain; charset=utf-8

bar
--XYZXYZXYZ--
""")

        self.assertEquals("".join(datagen), expected)
        datagen.reset()
        self.assertEquals("".join(datagen), expected)

    def test_reset_multiple_keys(self):
        params = poster.encode.MultipartParam.from_params(
                [("key", "value1"), ("key", "value2")])
        boundary = "XYZXYZXYZ"
        datagen, headers = poster.encode.multipart_encode(params, boundary)
        expected = unix2dos("""--XYZXYZXYZ
Content-Disposition: form-data; name="key"
Content-Type: text/plain; charset=utf-8

value1
--XYZXYZXYZ
Content-Disposition: form-data; name="key"
Content-Type: text/plain; charset=utf-8

value2
--XYZXYZXYZ--
""")

        encoded = "".join(datagen)
        self.assertEqual(encoded, expected)
        datagen.reset()
        encoded = "".join(datagen)
        self.assertEqual(encoded, expected)

    def test_reset_file(self):
        fp = StringIO.StringIO("file data")
        params = poster.encode.MultipartParam.from_params( [("foo", fp)] )
        boundary = "XYZXYZXYZ"
        datagen, headers = poster.encode.multipart_encode(params, boundary)

        expected = unix2dos("""--XYZXYZXYZ
Content-Disposition: form-data; name="foo"
Content-Type: text/plain; charset=utf-8

file data
--XYZXYZXYZ--
""")
        encoded = "".join(datagen)
        self.assertEqual(encoded, expected)
        datagen.reset()
        encoded = "".join(datagen)
        self.assertEqual(encoded, expected)

    def test_MultipartParam_cb(self):
        log = []
        def cb(p, current, total):
            log.append( (p, current, total) )
        p = poster.encode.MultipartParam("foo", "bar", cb=cb)
        boundary = "XYZXYZXYZ"

        datagen, headers = poster.encode.multipart_encode([p], boundary)

        "".join(datagen)

        l = p.get_size(boundary)
        self.assertEquals(log[-1], (p, l, l))

    def test_MultipartParam_file_cb(self):
        log = []
        def cb(p, current, total):
            log.append( (p, current, total) )
        p = poster.encode.MultipartParam("foo", fileobj=open("tests/test_encode.py"),
                cb=cb)
        boundary = poster.encode.gen_boundary()

        list(p.iter_encode(boundary))

        l = p.get_size(boundary)
        self.assertEquals(log[-1], (p, l, l))

    def test_multipart_encode_cb(self):
        log = []
        def cb(p, current, total):
            log.append( (p, current, total) )
        p = poster.encode.MultipartParam("foo", "bar")
        boundary = "XYZXYZXYZ"

        datagen, headers = poster.encode.multipart_encode([p], boundary, cb=cb)

        "".join(datagen)

        l = int(headers['Content-Length'])
        self.assertEquals(log[-1], (None, l, l))

class TestGenBoundary(TestCase):
    def testGenBoundary(self):
        boundary1 = poster.encode.gen_boundary()
        boundary2 = poster.encode.gen_boundary()

        self.assertNotEqual(boundary1, boundary2)
        self.assert_(len(boundary1) > 0)

class TestBackupGenBoundary(TestGenBoundary):
    _orig_import = __import__
    def setUp(self):
        # Make import uuid fail
        def my_import(name, *args, **kwargs):
            if name == 'uuid':
                raise ImportError("Disabled for testing")
            return self._orig_import(name, *args, **kwargs)
        __builtins__['__import__'] = my_import
        reload(poster.encode)

    def tearDown(self):
        __builtins__['__import__'] = self._orig_import
        reload(poster.encode)
