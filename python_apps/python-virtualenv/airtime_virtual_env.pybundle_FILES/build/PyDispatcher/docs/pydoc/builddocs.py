#! /usr/bin/env python
"""Script to automatically generate OpenGLContext documentation"""
import pydoc2

if __name__ == "__main__":
	excludes = [
		"math",
		"string",
	]
	stops = [
		"OpenGL.Demo.NeHe",
		"OpenGL.Demo.GLE",
		"OpenGL.Demo.da",
	]

	modules = [
		"pydispatch",
		"weakref",
	]	
	pydoc2.PackageDocumentationGenerator(
		baseModules = modules,
		destinationDirectory = ".",
		exclusions = excludes,
		recursionStops = stops,
	).process ()
	
