<?php
/*
 All Emoncms code is released under the GNU Affero General Public License.
 See COPYRIGHT.txt and LICENSE.txt.

 ---------------------------------------------------------------------
 Emoncms - open source energy visualisation
 Part of the OpenEnergyMonitor project:
 http://openenergymonitor.org
 */

class EmonLogger
{
    private $logfile = "";
    private $topic = "MAIN";

    public function __construct() {}
    
    public function set_logfile($logfile) {
        $this->logfile = $logfile;
    }
    
    public function set_topic($topic) {
        $this->topic = $topic;
    }

    public function info ($message){
        // print $message;
    }

    public function warn ($message){
        // print $message;
    }
}
