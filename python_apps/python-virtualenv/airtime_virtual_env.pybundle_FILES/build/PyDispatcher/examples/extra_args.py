#! /usr/bin/env python
from pydispatch import dispatcher
SIGNAL = 'my-first-signal'
SIGNAL2 = 'my-second-signal'

def handle_event( sender ):
    """Simple event handler"""
    print 'Signal was sent by', sender
dispatcher.connect( handle_event, signal=SIGNAL, sender=dispatcher.Any )
def handle_specific_event( sender, moo ):
    """Handle a simple event, requiring a "moo" parameter"""
    print 'Specialized event for %(sender)s moo=%(moo)r'%locals()
dispatcher.connect( handle_specific_event, signal=SIGNAL2, sender=dispatcher.Any )

first_sender = object()
second_sender = {}
def main( ):
    dispatcher.send( signal=SIGNAL, sender=first_sender )
    dispatcher.send( signal=SIGNAL, sender=second_sender )
    dispatcher.send( signal=SIGNAL2, sender=second_sender, moo='this' )

if __name__ == "__main__":
    main()
