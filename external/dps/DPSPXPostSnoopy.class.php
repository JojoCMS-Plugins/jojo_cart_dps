<?php
class DPSPXPostSnoopy extends DPSPXPost
{

  /**
   * Actions the DPS request POST to the server
   *
   * @return string The body of the response
   * @throws DPSBadResponseException If no response received from DPS
   */
  function doRequest ()
  {
    $xml = $this->assembleRequest();
    echo $xml;
    exit();
    if ($this->debug) {
      $this->addDebugMessage(sprintf("Outgoing XML:\n%s\n", $xml));
    }
    foreach (JOJO::listPlugins('external/snoopy/Snoopy.class.php') as $pluginfile) require_once($pluginfile);

    $snoopy = new Snoopy;
    if ($snoopy->submit($this->dps_url,array())) {
        if ($this->debug) {
            $this->addDebugMessage(sprintf("HTTP Response:\n%s\n", $response));
        }
        return $snoopy->results;
    } else {
        throw new DPSBadResponseException('No response received from DPS');
    }
  }
}