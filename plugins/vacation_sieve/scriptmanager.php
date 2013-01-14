<?php


class ScriptManager
{
    private $scriptCode;

    # Sections for the script building
    private $requires = array('"date"','"relational"','"vacation"');

    public function BuildScriptFromParams($params)
    {
        # Read variables
        $message = $params['message'];

        # Detect the format
        $format = 'text';
        if ( strpos('<html', $message) ) $format = 'html';
        
        # Build vacation scripts
        $execBlock =
            sprintf("## Generated by Vacation Sieve plugin for roundcube, the %s ##\n\n", date('d/m/Y'));

        # Include initial params in the script (disabled)
        # $execBlock .= sprintf("## Initial params: ##\n##PARAMS:[%s]\n\n", base64_encode(serialize($params)));

        # Add require blocks
        if ( $params['appendSubject'] ) $this->requires[] = '"variables"';
        $execBlock .= sprintf('require [%s];'."\n\n", join(",",$this->requires));

        # Add needed variables
        if ( $params['appendSubject'] )
        {
            $execBlock .= 'set "subject" "";';
            $execBlock .= 'if header :matches "subject" "*" { set "subject" "${1}"; }' ;
            $execBlock .= "\n\n";
        }

        # Build conditions
        $startDate = preg_replace('#(\d\d)/(\d\d)/(\d\d\d\d)#','$3-$2-$1',$params['start']);
        $endDate = preg_replace('#(\d\d)/(\d\d)/(\d\d\d\d)#','$3-$2-$1',$params['end']);
        $execBlock .= sprintf('if allof (currentdate :zone "+0100" :value "gt" "date" "%s", currentdate :zone "+0100" :value "lt" "date" "%s")'."\n",
            $startDate,
            $endDate);

        # Start to build the script
        $execBlock .= "{\n    vacation\n";

        $execBlock .= sprintf("        :days %d\n", $params['every']);
        
        # Add addresses
        if ( is_array($params['addresses']) )
        {
            $addresses = array();
            foreach ( $params['addresses'] as $address )
            {
                $address = preg_replace('/.*<(.*)>/', '"$1"', $address);
                $addresses[] = $address;
            }
            $execBlock .= sprintf("        :addresses [%s]\n", join(",",$addresses));
        }

        # Set subject
        $subject = str_replace('"', '\\"', $params['subject']);
        if ( $params['appendSubject'] )
            $execBlock .= sprintf('        :subject "%s: ${subject}"'."\n", $subject);
        else
            $execBlock .= sprintf('        :subject "%s"'."\n", $subject);

        # Use this as the handle number for now.
        $handle = substr(md5(mt_rand()),0,8);
        $execBlock .= sprintf('        :handle "%s"'."\n", $handle);

        # Add the from address
        $sendFrom = preg_replace('/.*<(.*)>/', '$1', $params['sendFrom']);
        $execBlock .= sprintf('        :from "%s"'."\n", $sendFrom);
        
        # Add the message in text format
        $message = str_replace('"', '\\"', $params['message']);
        $message = trim($message);
        $execBlock .= sprintf('        "%s";'."\n", $message);
        
        $execBlock .= "}";

        return $execBlock;
    }

    public function LoadParamsFromScript($script)
    {
    }


}