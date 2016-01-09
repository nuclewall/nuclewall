#! /usr/bin/perl -w
use strict;
use POSIX;

use vars qw(%RAD_CHECK);
use constant    RLM_MODULE_OK=>        2;
use constant    RLM_MODULE_NOOP=>      7;
use constant    RLM_MODULE_UPDATED=>   8;

sub authorize {
        if($RAD_CHECK{'NUCLE-Reset-Type'} =~ /monthly/i){
                $RAD_CHECK{'NUCLE-Start-Time'} = start_of_month()
        }

        if($RAD_CHECK{'NUCLE-Reset-Type'} =~ /weekly/i){
                $RAD_CHECK{'NUCLE-Start-Time'} = start_of_week()
        }

        if($RAD_CHECK{'NUCLE-Reset-Type'} =~ /daily/i){
                $RAD_CHECK{'NUCLE-Start-Time'} = start_of_day()
        }

        if(exists($RAD_CHECK{'NUCLE-Start-Time'})){
                return RLM_MODULE_UPDATED;
        }else{
                return RLM_MODULE_NOOP;
        }
}

sub start_of_month {
    my $reset_on = 1;
    my $unixtime;
    my ($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst)=localtime(time);
    if($mday < $reset_on ){
        $unixtime = mktime (0, 0, 0, $reset_on, $mon-1, $year, 0, 0);
    }else{
        $unixtime = mktime (0, 0, 0, $reset_on, $mon, $year, 0, 0);
    }
    return $unixtime;
}

sub start_of_week {
    my ($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst)=localtime(time);
    my $unixtime = mktime (0, 0, 0, $mday-$wday, $mon, $year, 0, 0);
    return $unixtime;
}

sub start_of_day {
    my ($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst)=localtime(time);
    my $unixtime = mktime (0, 0, 0, $mday, $mon, $year, 0, 0);
    return $unixtime;
}