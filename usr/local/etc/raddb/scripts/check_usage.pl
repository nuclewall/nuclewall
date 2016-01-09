#! usr/bin/perl -w

use strict;

use vars qw(%RAD_CHECK %RAD_REPLY);
use constant    RLM_MODULE_OK=>        2;
use constant    RLM_MODULE_UPDATED=>   8;
use constant    RLM_MODULE_REJECT=>    0;
use constant    RLM_MODULE_NOOP=>      7;

my $int_max = 4294967296;

sub authorize {

        if(exists($RAD_CHECK{'NUCLE-Total-Bytes'}) && exists($RAD_CHECK{'NUCLE-Used-Bytes'})){
                $RAD_CHECK{'NUCLE-Avail-Bytes'} = $RAD_CHECK{'NUCLE-Total-Bytes'} - $RAD_CHECK{'NUCLE-Used-Bytes'};
        }else{
                return RLM_MODULE_NOOP;
        }

        if($RAD_CHECK{'NUCLE-Avail-Bytes'} <= $RAD_CHECK{'NUCLE-Used-Bytes'}){
                if($RAD_CHECK{'NUCLE-Reset-Type'} ne 'never'){
                        $RAD_REPLY{'Reply-Message'} = "Maximum $RAD_CHECK{'NUCLE-Reset-Type'} usage exceeded";
                }else{
                        $RAD_REPLY{'Reply-Message'} = "Maximum usage exceeded";
                }
                return RLM_MODULE_REJECT;
        }

        if($RAD_CHECK{'NUCLE-Avail-Bytes'} >= $int_max){
                #Mikrotik's reply attributes
                $RAD_REPLY{'Mikrotik-Total-Limit'} = $RAD_CHECK{'NUCLE-Avail-Bytes'} % $int_max;
                $RAD_REPLY{'Mikrotik-Total-Limit-Gigawords'} = int($RAD_CHECK{'NUCLE-Avail-Bytes'} / $int_max );
                #Coova Chilli's reply attributes
                $RAD_REPLY{'ChilliSpot-Max-Total-Octets'} = $RAD_CHECK{'NUCLE-Avail-Bytes'} % $int_max;
                $RAD_REPLY{'ChilliSpot-Max-Total-Gigawords'} = int($RAD_CHECK{'NUCLE-Avail-Bytes'} / $int_max );

        }else{
                $RAD_REPLY{'Mikrotik-Total-Limit'} = $RAD_CHECK{'NUCLE-Avail-Bytes'};
                $RAD_REPLY{'ChilliSpot-Max-Total-Octets'} = $RAD_CHECK{'NUCLE-Avail-Bytes'};
        }
        return RLM_MODULE_UPDATED;
}