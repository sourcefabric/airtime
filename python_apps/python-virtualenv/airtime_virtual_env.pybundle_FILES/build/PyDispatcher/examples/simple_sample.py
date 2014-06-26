"""Simple sample showing basic usage pattern"""
from pydispatch import dispatcher

def doSomethingUseful( table, signal, sender ):
	"""Sample method to receive signals"""
	print '  doSomethingUseful', repr(table), signal, sender
def doSomethingElse( signal, **named ):
	"""Sample method to receive signals

	This method demonstrates the use of the **named
	parameter, which allows a method to receive all
	remaining parameters from the send call.
	"""
	print '  doSomethingElse', named
def doDefault( ):
	"""Sample method to receive All signals

	Note that this function will be registered for all
	signals from a given object.  It does not have the
	same interface as any of the other functions
	registered for those signals.  The system will
	automatically determine the appropriate calling
	signature for the function.
	"""
	print '  doDefault (no arguments)'

class Node(object):
	"""Sample object to send signals, note lack of dispatcher-aware code"""
	def __init__( self, name="an object" ):
		self.name = name
	def __repr__( self ):
		return "%s( %r )"%( self.__class__.__name__, self.name )

DO_LOTS = 0
DO_SOMETHING = ('THIS','IS','A','MORE','COMPLEX','SIGNAL')
DO_SOMETHING_ELSE = Node()

ourObjects = [
	Node(),
	Node(),
	Node(),
]
if __name__ == "__main__":
	# Establish some "routing" connections
	dispatcher.connect (
		doSomethingUseful,
		signal = DO_LOTS,
		sender = ourObjects[0],
	)
	dispatcher.connect (
		doSomethingElse,
		signal = DO_SOMETHING,
		sender = ourObjects[0],
	)
	dispatcher.connect(
		doDefault,
		signal = dispatcher.Any, # this is actually the default,
		sender = ourObjects[0],
	)
	print "Sending DO_LOTS from first object"
	dispatcher.send(
		signal = DO_LOTS,
		sender = ourObjects[0],
		table = "Table Argument",
	)
	print "Sending DO_SOMETHING from first object"
	dispatcher.send(
		signal = DO_SOMETHING,
		sender = ourObjects[0],
		table = "Table Argument",
	)
	print "Sending DO_SOMETHING_ELSE from first object"
	dispatcher.send(
		signal = DO_SOMETHING_ELSE,
		sender = ourObjects[0],
		table = "Table Argument",
	)
